{{--
    Per-telecaller grant form. Permissions the telecaller role already carries
    are shown ticked and disabled: they come from the role, so unticking them
    here would mean nothing. Everything else is a direct grant on this user.
--}}
@extends('layouts.master')

@section('title', 'Permissions — '.$user->name)

@section('content')
<div class="page-body">
    <div class="container-fluid">

        <div class="page-title">
            <div class="row">
                <div class="col-6">
                    <h3>Permissions — {{ $user->name }}</h3>
                </div>
                <div class="col-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i data-feather="home"></i></a></li>
                        <li class="breadcrumb-item"><a href="{{ route('access.permissions.index') }}">Permissions</a></li>
                        <li class="breadcrumb-item active">{{ $user->name }}</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-10">
                @include('partials.flash')

                <form method="POST" action="{{ route('access.permissions.update', $user->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="card">
                        <div class="card-header">
                            <h5>What {{ $user->name }} may do in the panel</h5>
                            <span class="f-light">
                                Greyed, ticked boxes come from the telecaller role and apply to every
                                telecaller. Saving replaces this person's individual grants with
                                whatever is ticked below.
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="row">
                                @foreach($groups as $group => $meta)
                                <div class="col-md-6 col-xl-4 mb-4">
                                    <h6 class="mb-2">{{ $meta['label'] }}</h6>
                                    @foreach($meta['abilities'] as $ability)
                                        @php
                                            $name = "{$group}.{$ability}";
                                            $viaRole = in_array($name, $fromRole, true);
                                            $checked = $viaRole || in_array($name, $direct, true);
                                        @endphp
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox"
                                                   id="perm-{{ $group }}-{{ $ability }}"
                                                   name="permissions[]" value="{{ $name }}"
                                                   @checked($checked) @disabled($viaRole)>
                                            <label class="form-check-label" for="perm-{{ $group }}-{{ $ability }}">
                                                {{ ucfirst($ability) }}
                                                @if($viaRole)
                                                    <span class="badge badge-light-primary ms-1">from role</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>

                            <div class="alert alert-light-primary" role="alert">
                                User administration and access control itself are admin-only, so they are
                                not offered here.
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <a class="btn btn-light" href="{{ route('access.permissions.index') }}">Cancel</a>
                            <a class="btn btn-outline-primary" href="{{ route('telecallers.show', $user->id) }}">
                                View account
                            </a>
                            <button class="btn btn-primary" type="submit">Save permissions</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
