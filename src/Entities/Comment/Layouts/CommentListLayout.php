<?php

namespace CmsOrbit\Comments\Entities\Comment\Layouts;

use CmsOrbit\Comments\Entities\Comment\Comment;
use App\Settings\Entities\User\User;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class CommentListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'comments';

    /**
     * Get the table columns.
     *
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('id', 'ID')
                ->sort()
                ->filter(TD::FILTER_TEXT),

            TD::make('content', __('Content'))
                ->sort()
                ->filter(TD::FILTER_TEXT)
                ->render(function (Comment $comment) {
                    return \Str::limit(strip_tags($comment->getAttribute('content')), 50);
                }),

            TD::make('author', __('Author'))
                ->sort()
                ->render(function (Comment $comment) {
                    if ($comment->getAttribute('author_type') && $comment->getAttribute('author_id')) {
                        return $comment->author?->name ?? __('Unknown');
                    }
                    return $comment->getAttribute('guest_name') ?? __('Guest');
                }),

            TD::make('commentable', __('Target'))
                ->render(function (Comment $comment) {
                    $type = class_basename($comment->getAttribute('commentable_type'));
                    return "{$type} #{$comment->getAttribute('commentable_id')}";
                }),

            TD::make('status', __('Status'))
                ->render(function (Comment $comment) {
                    $status = [];
                    if (!$comment->getAttribute('is_approved')) $status[] = '<span class="badge bg-warning">미승인</span>';
                    if ($comment->getAttribute('is_secret')) $status[] = '<span class="badge bg-info">비밀</span>';
                    if ($comment->getAttribute('is_spam')) $status[] = '<span class="badge bg-danger">스팸</span>';
                    if ($comment->getAttribute('parent_id')) $status[] = '<span class="badge bg-secondary">답글</span>';
                    
                    return empty($status) ? '<span class="badge bg-success">정상</span>' : implode(' ', $status);
                }),

            TD::make('created_at', __('Created'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(function (Comment $comment) {
                    return $comment->getAttribute('created_at')?->format('Y-m-d H:i');
                }),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(function (Comment $comment) {
                    $actions = $this->getActions($comment);
                    if(count($actions) < 1) return null;
                    return DropDown::make()->icon('bs.three-dots')->list($actions);
                }),
        ];
    }

    private function getActions(Comment $comment): array
    {
        /** @var User $user */
        $user = Auth::user();
        $actions = [];

        if($user->hasAccess('settings.entities.comments.edit')) {
            $actions[] = Link::make(__('Edit'))
                ->route('settings.entities.comments.edit', $comment->getKey())
                ->icon('bs.pencil');
        }

        if($user->hasAccess('settings.entities.comments.delete')) {
            $actions[] = Button::make(__('Delete'))
                ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                ->method('remove', ['id' => $comment->getKey()])
                ->icon('bs.trash3');
        }

        return $actions;
    }
}
