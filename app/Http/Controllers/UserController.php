<?php

namespace App\Http\Controllers;

date_default_timezone_set('America/New_York');

use Illuminate\Routing\Controller as BaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App\User;
use App\Http\UserRepository;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * @var Symfony\Component\HttpFoundation\JsonResponse
     */
    protected $response;

    public function __construct()
    {
        $this->response = new JsonResponse();
    }

    /**
     * @api GEt /api/users
     */
    public function getUsers()
    {
        $repo = new UserRepository();
        $users = $repo->getUsers();

        $this->response->setStatusCode(Response::HTTP_OK);
        $this->response->setData([
            'data' => $users,
            'message' => "Users",
            'success' => true,
        ]);
        $this->response->send();
        return;
    }

    /**
     * @api POST /api/login
     */
    public function login(Request $request)
    {
        $repo = new UserRepository();
        $users = $repo->getUsers();

        if (!$username = $request->input('username')) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Username is required",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        if (!$password = $request->input('password')) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Password is required",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $user = $repo->getUserByUsername($username);

        if ($user->password === $password) {
            $this->response->setStatusCode(Response::HTTP_OK);
            $this->response->setData([
                'data' => ['user' => $user],
                'message' => "Login successful!",
                'success' => true,
            ]);
            $this->response->send();
            return;
        } else {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => ['password'],
                'message' => "Invalid password!",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $this->response->setData([
            'data' => ['username'],
            'message' => "Unknown username!",
            'success' => false,
        ]);
        $this->response->send();
        return;
    }

    /**
     * @api GET /api/users/{id}
     */
    public function getByUserId($id)
    {
        $repo = new UserRepository();
        $users = $repo->getUsers();

        foreach ($users as $user) {
            if ($user->id == $id) {
                $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $this->response->setData([
                    'data' => ['user' => $user],
                    'message' => "User found!",
                    'success' => true,
                ]);
                $this->response->send();
                return;
            }
        }

        $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $this->response->setData([
            'data' => ['id' => $id],
            'message' => "No user found for ID",
            'success' => false,
        ]);
        $this->response->send();
        return;
    }

    /**
     * @api PATCH /api/users/password
     */
    public function patchPassword(Request $request) {
        $password = $request->input('password');
        $repeat = $request->input('repeat');
        $id = $request->input('id');


        if ($password !== $repeat) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Passwords do not match",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        if (strlen($password) < 6) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => [],
                'message' => "Password must be 6 characters minimum",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $repo = new UserRepository();
        $users = $repo->getUsers();

        $exists = false;
        foreach ($users as $user) {
            if ($user->id === $id) {
                $user->password = $password;
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => ['id' => $id],
                'message' => "No user found for ID",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $repo->saveUsers($users);
    }

    /**
     * @api PATCH /api/users/edit
     */
    public function patchUser(Request $request)
    {
        $user = $request->input('user');

        $change_password = $request->input('changePassword') ? $request->input('changePassword') : false;

        $error_data = [];
        if ($user['username'] === null || $user['username'] === '') {
            $error_data['username'] = 'Username is required';
        }

        if ($user['name'] === null || $user['name'] === '') {
            $error_data['name'] = 'Name is required';
        }

        if ($user['role'] === null || $user['role'] === '') {
            $error_data['role'] = 'Role is required';
        }

        if ($change_password) {
            if (!array_key_exists('password', $user) || $user['password'] === null || $user['password'] === '') {
                $error_data['password'] = 'Password is required';
            } else {
                if ($user['password'] !== $request->input('repeat')) {
                    $error_data['password'] = 'Passwords do not match';
                } else if (strlen($user['password']) < 6) {
                    $error_data['password'] = 'Password must be 6 characters minimum';
                }
            }
        }

        if (!empty($error_data)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => $error_data,
                'message' => "Error!",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $repo = new UserRepository();
        $users = $repo->getUsers();

        foreach ($users as $item) {
            if ($item->username === $user['username'] && $item->id !== $user['id']) {
                $error_data['username'] = 'Username is not unique';
            }
        }

        if (!empty($error_data)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => $error_data,
                'message' => "Error!",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $exists = false;
        foreach ($users as $item) {
            if ($item->id === $user['id']) {
                $item->username = $user['username'];
                $item->name = $user['name'];
                $item->role = $user['role'];
                $item->password = $user['password'];
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => ['user' => 'User does not exist'],
                'message' => "Error!",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }
        $repo->saveUsers($users);
    }

    private function createToken() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @api POST /api/users
     */
    public function postUser(Request $request)
    {
        $user = $request->input('user');
        $repeat = $request->input('repeat');

        $error_data = [];
        if (!array_key_exists('username', $user) || $user['username'] === null || $user['username'] === '') {
            $error_data['username'] = 'Username is required';
        }

        if (!array_key_exists('name', $user) || $user['name'] === null || $user['name'] === '') {
            $error_data['name'] = 'Name is required';
        }

        if (!array_key_exists('role', $user) || $user['role'] === null || $user['role'] === '') {
            $error_data['role'] = 'Role is required';
        }

        if (!array_key_exists('password', $user) || $user['password'] === null || $user['password'] === '') {
            $error_data['password'] = 'Password is required';
        } else {
            if ($user['password'] !== $repeat) {
                $error_data['password'] = 'Passwords do not match';
            } else if (strlen($user['password']) < 6) {
                $error_data['password'] = 'Password must be 6 characters minimum';
            }
        }

        if (!empty($error_data)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => $error_data,
                'message' => "Error!",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $repo = new UserRepository();
        $users = $repo->getUsers();

        $last_id = 0;
        foreach ($users as $item) {
            if ($item->username === $user['username']) {
                $error_data['username'] = 'Username is not unique';
                break;
            }
            if ($item->id > $last_id) {
                $last_id = $item->id;
            }
        }
        if (!empty($error_data)) {
            $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $this->response->setData([
                'data' => $error_data,
                'message' => "Error!",
                'success' => false,
            ]);
            $this->response->send();
            return;
        }

        $last_id++;
        $user['id'] = $last_id;
        $user['token'] = $this->createToken();
        $users[] = new User($user);
        $repo->saveUsers($users);
    }

    /**
     * @api DELETE /api/users
     */
    public function deleteUser(Request $request)
    {
        $id = $request->input('id');
        $repo = new UserRepository();
        $users = $repo->getUsers();

        $updated_users = [];
        foreach ($users as $user) {
            if ($user->id !== $id) {
                $updated_users[] = $user;
            }
        }
        $repo->saveUsers($updated_users);
    }
}
