<?php

namespace App\Modules\ClientTypes\Models;

use App\Modules\ClientTypes\Database\Factories\ClientTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One kind of client: doctor, hospital, medical store, or whatever else an
 * admin adds. State only — querying lives in the repository.
 *
 * `slug` is what every `persons.type` row stores, so it is set at creation
 * and never edited afterwards (see the create migration).
 */
#[Fillable(['slug', 'label', 'colour', 'has_specialization', 'is_active', 'sort_order'])]
#[UseFactory(ClientTypeFactory::class)]
class ClientType extends Model
{
    /** @use HasFactory<ClientTypeFactory> */
    use HasFactory;

    /** Badge colours offered by the form; each is a Cuba badge suffix. */
    public const COLOURS = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];

    /** The three the product ships with. They may be edited, not deleted. */
    public const BUILT_IN = ['doctor', 'hospital', 'medical'];

    protected $table = 'client_types';

    protected function casts(): array
    {
        return [
            'has_specialization' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function badgeClass(): string
    {
        return 'badge-light-'.$this->colour;
    }

    public function isBuiltIn(): bool
    {
        return in_array($this->slug, self::BUILT_IN, true);
    }
}
