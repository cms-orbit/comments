<?php

namespace CmsOrbit\Comments\Entities\Comment\Screens;

use CmsOrbit\Comments\Entities\Comment\Comment;
use App\Settings\Entities\User\User;
use CmsOrbit\Comments\Entities\Comment\Layouts\CommentListLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Toast;

class CommentListScreen extends Screen
{
    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'comments' => Comment::filters()->defaultSort('id')->paginate(),
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return __('Comments Management');
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return __('Manage all comments, replies, and user interactions');
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $commands = [];
        /** @var User $user */
        $user = Auth::user();
        if($user->hasAccess('settings.entities.comments.create')) $commands[] = Link::make(__('Create'))->icon('bs.plus-circle')->route('settings.entities.comments.create');

        return $commands;
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]|string[]
     */
    public function layout(): iterable
    {
        return [
            CommentListLayout::class,
        ];
    }

    public function remove(Request $request): void
    {
        Comment::findOrFail($request->get('id'))->delete();

        Toast::info(__('Comment was removed'));
    }
}
