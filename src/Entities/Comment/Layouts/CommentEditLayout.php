<?php

namespace CmsOrbit\Comments\Entities\Comment\Layouts;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Quill;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class CommentEditLayout extends Rows
{
    /**
     * Get the fields elements to be displayed.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('comment.id')
                ->type('hidden'),

            Group::make([
                Input::make('comment.commentable_type')
                    ->title(__('Target Type'))
                    ->placeholder(__('Model class name')),

                Input::make('comment.commentable_id')
                    ->title(__('Target ID'))
                    ->type('number')
                    ->placeholder(__('Target model ID')),
            ]),

            Quill::make('comment.content')
                ->title(__('Content'))
                ->placeholder(__('Enter comment content'))
                ->required(),

            Group::make([
                Input::make('comment.author_type')
                    ->title(__('Author Type'))
                    ->placeholder(__('Author model type')),

                Input::make('comment.author_id')
                    ->title(__('Author ID'))
                    ->type('number')
                    ->placeholder(__('Author ID')),
            ]),

            Input::make('comment.parent_id')
                ->title(__('Parent Comment ID'))
                ->type('number')
                ->placeholder(__('Reply to comment ID')),

            Group::make([
                Input::make('comment.guest_name')
                    ->title(__('Guest Name'))
                    ->placeholder(__('Guest user name')),

                Input::make('comment.guest_email')
                    ->title(__('Guest Email'))
                    ->type('email')
                    ->placeholder(__('Guest email address')),
            ]),

            Input::make('comment.guest_password')
                ->title(__('Guest Password'))
                ->type('password')
                ->placeholder(__('Password for guest comment')),

            Group::make([
                CheckBox::make('comment.is_approved')
                    ->title(__('Approved'))
                    ->placeholder(__('Is comment approved'))
                    ->value(1)
                    ->sendTrueOrFalse(),

                CheckBox::make('comment.is_secret')
                    ->title(__('Secret'))
                    ->placeholder(__('Is secret comment'))
                    ->sendTrueOrFalse(),

                CheckBox::make('comment.is_spam')
                    ->title(__('Spam'))
                    ->placeholder(__('Is spam comment'))
                    ->sendTrueOrFalse(),

                CheckBox::make('comment.notify_reply')
                    ->title(__('Notify Reply'))
                    ->placeholder(__('Notify when reply is added'))
                    ->sendTrueOrFalse(),
            ]),

            Group::make([
                Input::make('comment.ip_address')
                    ->title(__('IP Address'))
                    ->placeholder(__('Client IP address'))
                    ->readonly(),

                Input::make('comment.depth')
                    ->title(__('Depth'))
                    ->type('number')
                    ->placeholder(__('Comment depth level'))
                    ->readonly(),
            ]),

            TextArea::make('comment.user_agent')
                ->title(__('User Agent'))
                ->placeholder(__('Client user agent'))
                ->rows(3)
                ->readonly(),
        ];
    }
}
