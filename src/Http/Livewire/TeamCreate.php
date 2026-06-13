<?php

declare(strict_types=1);

namespace IvanBaric\Velora\Http\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class TeamCreate extends Component
{
    public string $name = '';

    public bool $modal = false;

    public function mount(bool $modal = false): void
    {
        abort(403);
    }

    public function createTeam(): void
    {
        abort(403);
    }

    public function render(): View
    {
        $view = view('velora::livewire.team-create');

        if ($this->modal) {
            return $view;
        }

        return $view->layout((string) config('velora.views.layouts.app', 'layouts.app'));
    }
}
