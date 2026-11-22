<?php

namespace App\Modules\Messages\Repositories;

use App\Core\Repositories\EloquentRepository;
use App\Modules\Messages\Models\Message;
use App\Modules\Messages\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every `messages` query in the application lives here.
 */
class EloquentMessageRepository extends EloquentRepository implements MessageRepositoryInterface
{
    protected function model(): Model
    {
        return new Message;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        foreach (['from_user_id' => 'from_user_id', 'to_user_id' => 'to_user_id',
            'person_id' => 'person_id'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (($read = $filters['read'] ?? null) !== null && $read !== '') {
            $read === 'unread' ? $query->whereNull('read_at') : $query->whereNotNull('read_at');
        }

        /**
         * The panel scope for a non-admin: everything they sent or were sent.
         * A message has two owners, so this is an OR rather than one column —
         * and it is wrapped, or it would escape any filter beside it.
         */
        if (! empty($filters['participant_id'])) {
            $id = $filters['participant_id'];
            $query->where(fn (Builder $q) => $q->where('from_user_id', $id)->orWhere('to_user_id', $id));
        }

        if (($search = trim((string) ($filters['search'] ?? ''))) !== '') {
            $like = "%{$search}%";
            $query->where(function (Builder $q) use ($like) {
                $q->where('subject', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhereHas('person', fn (Builder $p) => $p->where('name', 'like', $like))
                    ->orWhereHas('sender', fn (Builder $s) => $s->where('name', 'like', $like))
                    ->orWhereHas('recipient', fn (Builder $r) => $r->where('name', 'like', $like));
            });
        }

        return $query;
    }

    public function paginateWithRelations(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return $this->applyFilters($this->query(), $filters)
            ->with(['sender', 'recipient', 'person'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function unreadCountFor(int $recipientId): int
    {
        return $this->query()
            ->where('to_user_id', $recipientId)
            ->whereNull('read_at')
            ->count();
    }

    public function summary(array $filters): array
    {
        // A fresh builder per count: reusing one would stack the constraints.
        $base = fn () => $this->applyFilters($this->query(), $filters);

        return [
            'total' => $base()->count(),
            'unread' => $base()->whereNull('read_at')->count(),
            'senders' => $base()->distinct()->count('from_user_id'),
            'recipients' => $base()->distinct()->count('to_user_id'),
        ];
    }

    /**
     * Messages sent per sender in the range: `from_user_id => count`. One
     * column on the telecaller report — "did they pass the lead on".
     *
     * @return array<int, int>
     */
    public function sentCountsByUser(array $filters = []): array
    {
        return $this->applyFilters($this->query(), $filters)
            ->selectRaw('from_user_id, count(*) as total')
            ->groupBy('from_user_id')
            ->pluck('total', 'from_user_id')
            ->map(intval(...))
            ->all();
    }

    public function markRead(int $recipientId, ?int $messageId = null): int
    {
        return $this->query()
            ->where('to_user_id', $recipientId)
            ->whereNull('read_at')
            ->when($messageId !== null, fn (Builder $q) => $q->whereKey($messageId))
            ->update(['read_at' => now()]);
    }
}
