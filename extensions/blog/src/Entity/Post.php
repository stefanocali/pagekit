<?php

namespace Pagekit\Blog\Entity;

use Pagekit\Application as App;
use Pagekit\Comment\CommentsTrait;
use Pagekit\Database\ORM\ModelTrait;
use Pagekit\System\Entity\DataTrait;
use Pagekit\User\Entity\AccessTrait;

/**
 * @Entity(tableClass="@blog_post", eventPrefix="blog.post")
 */
class Post implements \JsonSerializable
{
    use AccessTrait, CommentsTrait, DataTrait, ModelTrait;

    /* Post draft status. */
    const STATUS_DRAFT = 0;

    /* Post pending review status. */
    const STATUS_PENDING_REVIEW = 1;

    /* Post published. */
    const STATUS_PUBLISHED = 2;

    /* Post unpublished. */
    const STATUS_UNPUBLISHED = 3;

    /** @Column(type="integer") @Id */
    protected $id;

    /** @Column(type="string") */
    protected $title;

    /** @Column(type="string") */
    protected $slug;

    /** @Column(type="integer") */
    protected $user_id;

    /** @Column(type="datetime")*/
    protected $date;

    /** @Column(type="text") */
    protected $content = '';

    /** @Column(type="text") */
    protected $excerpt = '';

    /** @Column(type="smallint") */
    protected $status;

    /** @Column(type="datetime") */
    protected $modified;

    /** @Column(type="boolean") */
    protected $comment_status;

    /** @Column(type="integer") */
    protected $comment_count = 0;

    /** @Column(type="json_array") */
    protected $data;

    /**
     * @BelongsTo(targetEntity="Pagekit\User\Entity\User", keyFrom="user_id")
     */
    protected $user;

    /**
     * @HasMany(targetEntity="Comment", keyFrom="id", keyTo="post_id")
     * @OrderBy({"created" = "DESC"})
     */
    protected $comments;

    /**
     * @var bool
     */
    protected $commentable;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getExcerpt()
    {
        return $this->excerpt;
    }

    public function setExcerpt($excerpt)
    {
        $this->excerpt = $excerpt;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getModified()
    {
        return $this->modified;
    }

    public function setModified(\DateTime $modified)
    {
        $this->modified = $modified;
    }

    public static function getStatuses()
    {
        return [
            self::STATUS_PUBLISHED      => __('Published'),
            self::STATUS_UNPUBLISHED    => __('Unpublished'),
            self::STATUS_DRAFT          => __('Draft'),
            self::STATUS_PENDING_REVIEW => __('Pending Review')
        ];
    }

    public function getStatusText()
    {
        $statuses = self::getStatuses();

        return isset($statuses[$this->status]) ? $statuses[$this->status] : __('Unknown');
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
        $this->user_id = $user->getId();
    }

    public function isCommentable()
    {
        return $this->commentable;
    }

    public function setCommentable($commentable)
    {
        $this->commentable = $commentable;
    }

    public function isPublished()
    {
        return $this->status === self::STATUS_PUBLISHED && $this->date < new \DateTime;
    }

    public function isAccessible()
    {
        return $this->isPublished() && $this->hasAccess(App::user());
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $post = $this->toJson();

        if ($post['user']) {
            $post['author'] = $post['user']->getUsername();
            unset($post['user']);
        }

        if ($post['comments']) {
            $post['comments_pending'] = count(array_filter($post['comments'], function($comment) { return $comment->getStatus() == Comment::STATUS_PENDING; }));
            unset($post['comments']);
        }

        $post['isPublished']  = $this->isPublished();
        $post['isAccessible'] = $this->isAccessible();
        $post['url']          = App::url('@blog/id', ['id' => $this->id ?: 0]);

        return $post;
    }

    /**
     * @PreSave
     */
    public function preSave()
    {
        $this->modified = new \DateTime;

        $i = 2;
        $id = $this->id;

        while (self::where('slug = ?', [$this->slug])->where(function($query) use($id) { if ($id) $query->where('id <> ?', [$id]); })->first()) {
            $this->slug = preg_replace('/-\d+$/', '', $this->slug).'-'.$i++;
        }
    }

    /**
     * @PreDelete
     */
    public function preDelete()
    {
        self::getConnection()->delete('@blog_comment', ['post_id' => $this->getId()]);
    }

    /**
     * Updates the comments info on post.
     *
     * @param int $id
     */
    public static function updateCommentInfo($id)
    {
        $query = Comment::where(['post_id' => $id, 'status' => Comment::STATUS_APPROVED]);

        self::where(compact('id'))->update([
                'comment_count' => $query->count()
            ]
        );
    }

    /**
     * Get all users who have written an article
     */
    public static function getAuthors()
    {
        return self::query()->select('user_id', 'name')->groupBy('user_id', 'name')->join('@system_user', 'user_id = @system_user.id')->execute()->fetchAll();
    }
}
