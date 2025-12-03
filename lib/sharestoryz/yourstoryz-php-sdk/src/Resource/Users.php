<?php

namespace YourStoryz\PhpSdk\Resource;

use Saloon\Http\Response;
use YourStoryz\PhpSdk\Requests\Users\GetPublishedVideos;
use YourStoryz\PhpSdk\Requests\Users\GetStories;
use YourStoryz\PhpSdk\Resource;

class Users extends Resource
{
    /**
     * @param  int  $userId  The ID of the user.
     */
    public function publishedVideos(int $userId): Response
    {
        return $this->connector->send(new GetPublishedVideos($userId));
    }

    /**
     * @param  int  $userId  The ID of the user.
     */
    public function stories(int $userId): Response
    {
        return $this->connector->send(new GetStories($userId));
    }
}
