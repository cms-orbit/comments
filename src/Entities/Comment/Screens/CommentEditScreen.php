<?php
namespace CmsOrbit\Comments\Entities\Comment\Screens;

use CmsOrbit\Comments\Entities\Comment\Comment;
use App\Settings\Entities\User\User;
use App\Settings\Extends\OrbitLayout;
use CmsOrbit\Comments\Entities\Comment\Layouts\CommentEditLayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Toast;

class CommentEditScreen extends Screen
{
    /**
     * @var Comment
     */
    public $comment;

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(Comment $comment): iterable
    {
        return [
            'comment' => $comment,
        ];
    }

    /**
     * The name of the screen displayed in the header.
     *
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->comment->exists ? 'Edit Comment' : 'Create Comment';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        $commands = [];
        if($this->comment->exists) {
            /** @var User $user */
            $user = Auth::user();

            if($user->hasAccess('settings.entities.comments.delete')) {
                $commands[] = Button::make(__('Remove'))
                    ->icon('trash')
                    ->method('remove');
            }
        }

        $commands[] = Button::make(__('Save'))
            ->icon('check')
            ->method('save');

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
            OrbitLayout::block(CommentEditLayout::class)
                ->title(__('Comment Details'))
                ->description(__('Edit comment content, status, and related information.'))
                ->commands(
                    Button::make(__('Save'))
                        ->type(Color::BASIC)
                        ->icon('bs.check-circle')
                        ->canSee($this->comment->exists)
                        ->method('save')
                ),
        ];
    }

    /**
     * Define the permissions required to view this screen.
     *
     * @return iterable|null
     */
    public function permission(): ?iterable
    {
        return [
            'settings.entities.comments.create',
            'settings.entities.comments.edit',
        ];
    }

    /**
     * Save the Comment.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function save(Request $request, Comment $comment)
    {
        $data = $request->input('comment');
        
        // Set automatic fields for new comments
        if (!$comment->exists) {
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();
            
            // Calculate depth based on parent
            if (!empty($data['parent_id'])) {
                $parent = Comment::find($data['parent_id']);
                $data['depth'] = $parent ? $parent->getAttribute('depth') + 1 : 0;
            } else {
                $data['depth'] = 0;
            }
        }
        
        // Validate required fields
        $request->validate([
            'comment.content' => 'required|string',
            'comment.commentable_type' => 'nullable|string',
            'comment.commentable_id' => 'nullable|integer',
            'comment.author_type' => 'nullable|string',
            'comment.author_id' => 'nullable|integer',
            'comment.parent_id' => 'nullable|integer|exists:comments,id',
            'comment.guest_name' => 'nullable|string|max:255',
            'comment.guest_email' => 'nullable|email|max:255',
            'comment.guest_password' => 'nullable|string|max:255',
        ]);

        $comment->fill($data);
        $comment->save();

        Toast::info(__('Comment was saved.'));

        return redirect()->route('settings.entities.comments');
    }

    /**
     * Remove the Comment.
     *
     * @param \CmsOrbit\Comments\Entities\Comment\Comment $comment
     */
    public function remove(Comment $comment)
    {
        /** @var User $user */
        $user = Auth::user();

        if($user->hasAccess('settings.entities.comments.delete')) {
            $comment->delete();
            Toast::info(__('Comment was removed.'));
        }else{
            Toast::error(__('You do not have permission to delete this Comment.'));
        }

        return redirect()->route('settings.entities.comments');
    }
}
