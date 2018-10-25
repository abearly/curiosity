<?php

namespace App\Http;

use App\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserRepository
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var Symfony\Component\HttpFoundation\JsonResponse
     */
    protected $response;

    /**
     * @var array
     */
    protected $users;

    public function __construct()
    {
        $path = storage_path();
        $this->file = $path.'/users.json';
        $json = file_get_contents($this->file);
        if (!$json) {
            throw new Exception();
        }

        $users = json_decode($json);

        foreach ($users as $user) {
            $data = [
                'id' => $user->id,
                'username' => $user->username,
                'password' => $user->password,
                'name' => $user->name,
                'role' => $user->role,
                'token' => $user->token,
            ];
            $this->users[] = new User($data);
        }

        $this->response = new JsonResponse();
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function getUserByUsername($username)
    {
        foreach ($this->users as $user) {
            if ($user->username === $username) {
                return $user;
            }
        }

        $this->response->setStatusCode(Response::HTTP_OK);
        $this->response->setData([
            'data' => ['user' => $user],
            'message' => "Login successful!",
            'success' => true,
        ]);
        $this->response->send();
        return;
    }

    public function saveUsers($users)
    {
        $this->users = $users;
        if (!file_put_contents($this->file, json_encode($users))) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Could not save users",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }
        $this->response->setStatusCode(Response::HTTP_OK);
        $this->response->setData([
            'data' => $users,
            'message' => "Saved!",
            'success' => true,
        ]);
        $this->response->send();
        return;
    }
}
