<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;

class ChatController extends Controller
{
    //Folder
    public function addFolder(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'name' => 'required',
            'expanded' => 'required',
            'order' => 'required',
        ]);

        $id = $request->id;
        $name = $request->name;
        $expanded = $request->expanded;
        $order_index = $request->order;
        $color = $request->color;
        $user = Auth::user();

        $folders = DB::table('folder')
            ->where('user_id', $user->id)
            ->orderBy('updated_at')
            ->get();
        $count = 0;
        foreach ($folders as $folder) {
            DB::table('folder')->where('id', $folder->id)->update(['order_index' => ++$count]);
        }

        DB::table('folder')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'name' => $name,
            'expanded' => $expanded,
            'order_index' => $order_index,
            'color' => $color
        ]);

        $res = new stdClass();
        $res->status = 1;
        $res->msg = 'Folder created successfully';
        return response()->json($res);
    }
    public function updateFolder(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'name' => 'required',
            'expanded' => 'required',
            'order' => 'required'
        ]);

        $id = $request->id;
        $name = $request->name;
        $expanded = $request->expanded;
        $order_index = $request->order;
        $color = $request->color;

        DB::table('folder')->where('id', $id)->update([
            'id' => $id,
            'name' => $name,
            'expanded' => $expanded,
            'order_index' => $order_index,
            'color' => $color,
            'updated_at' => now()
        ]);

        $res = new stdClass();
        $res->status = 1;
        $res->msg = 'Folder updated successfully';
        return response()->json($res);
    }
    public function deleteFolder(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $id = $request->id;

        $value = DB::table('folder')->where('id', $id)->delete();

        $res = new stdClass();
        if ($value == 0) {
            $res->status = 0;
            $res->msg = 'Folder not found';
        } else {
            $res->status = 1;
            $res->msg = 'Folder deleted successfully';
        }

        return response()->json($res);
    }

    //Chat
    public function detail(Request $request)
    {
        $user = Auth::user();

        $state = new stdClass();
        $state->folders = DB::table('folder')->where('user_id', $user->id)->select(['id', 'name', 'expanded', 'order_index as order', 'color'])->get();
        $state->chats = DB::table('chat')->where('user_id', $user->id)->orderByDesc('updated_at')->get();
        $state->chat_index = $user->chat_index;

        $token_count = DB::table('token_count')->where('user_id', $user->id)->first();
        if ($token_count->updated_at < date("Y-m-d")) {
            DB::table('token_count')->where('user_id', $user->id)->update([
                'daily_used' => 0,
                'updated_at' => now()
            ]);
            $state->last_token = false;
        }
        else $state->last_token = ($token_count->daily_used >= $token_count->max_token);

        if (empty($state->chats)) {
            $res = new stdClass();
            $res->status = 0;
            $res->msg = 'Empty chat';
            return response()->json($res, 204);
        } else {
            foreach ($state->chats as $chat) {
                $chat->config = json_decode($chat->config);
                $chat->folder = $chat->folder_id;
                $chat->messages = DB::table('message')->where('chat_id', $chat->id)->select(['role', 'content'])->get();
                unset($chat->user_id, $chat->folder_id);
            }
            return response()->json($state, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

    }
    public function updateCurrentChatIndex(Request $request)
    {
        $request->validate([
            'chat_index' => 'required | integer'
        ]);

        $chat_index = $request->chat_index;
        $user = Auth::user();

        DB::table('users')->where('id', $user->id)->update([
            'chat_index' => $chat_index
        ]);

        $res = new stdClass();
        $res->status = 1;
        $res->msg = 'Update current chat index successfully';

        return response()->json($res);
    }

    public function addChat(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'config' => 'required',
            'title' => 'required',
            'titleSet' => 'required'
        ]);

        $id = $request->id;
        $config = $request->config;
        $title = $request->title;
        $titleSet = $request->titleSet;
        $folder_id = $request->folder_id;

        $user = Auth::user();

        DB::table('chat')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'config' => $config,
            'title' => $title,
            'titleSet' => $titleSet,
            'folder_id' => $folder_id
        ]);

        $user->chat_index = 0;
        $user->save();

        $res = new stdClass();
        $res->status = 1;
        $res->msg = "Create new chat successfully";
        return response()->json($res);
    }
    public function updateChat(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'config' => 'required',
            'title' => 'required',
            'titleSet' => 'required'
        ]);

        $id = $request->id;
        $config = $request->config;
        $title = $request->title;
        $titleSet = $request->titleSet;
        $folder_id = $request->folder_id;

        $user = Auth::user();

        DB::table('chat')->where('id', $id)->where('user_id', $user->id)->update([
            'config' => $config,
            'title' => $title,
            'titleSet' => $titleSet,
            'folder_id' => $folder_id
        ]);

        $res = new stdClass();
        $res->status = 1;
        $res->msg = "Update chat successfully";
        return response()->json($res);
    }
    public function deleteChat(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $id = $request->id;
        $user = Auth::user();

        $value = DB::table('chat')->where('id', $id)->where('user_id', $user->id)->delete();
        
        $res = new stdClass();
        if ($value == 0) {
            $res->status = 0;
            $res->msg = "Chat not found";
            return response()->json($res);
        } else {
            $res->status = 1;
            $res->msg = "Delete chat successfully";
            return response()->json($res);
        }
    }

    //Message
    public function addMessage(Request $request)
    {
        $request->validate([
            'chat_id' => 'required',
            'role' => 'required',
            'content' => 'nullable'
        ]);

        $chat_id = $request->chat_id;
        $role = $request->role;
        $content = $request->content ?? "";

        $user = Auth::user();

        $chat_exist = DB::table('chat')->where('id', $chat_id)->first();
        if ($chat_exist != null) {
            DB::table('message')->insert([
                'chat_id' => $chat_id,
                'role' => $role,
                'content' => $content
            ]);
            if ($role == "user")
                DB::table('token_count')->where('user_id', $user->id)->incrementEach([
                    'daily_used' => 1,
                    'total_used' => 1
                ]);
            $res = new stdClass();
            $res->status = 1;
            $res->msg = "Create new chat and message successfully";
            return response()->json($res);
        } else {
            $res = new stdClass();
            $res->status = 0;
            $res->msg = "Chat not found";
            return response()->json($res);
        }
    }
    public function updateMessage(Request $request)
    {
        $request->validate([
            'chat_id' => 'required',
            'role' => 'required',
            'content' => 'required'
        ]);

        $chat_id = $request->chat_id;
        $role = $request->role;
        $content = $request->content;

        $user = Auth::user();

        $chat_exist = DB::table('chat')->where('id', $chat_id)->first();
        if ($chat_exist != null) {
            DB::table('message')->insert([
                'chat_id' => $chat_id,
                'role' => $role,
                'content' => $content
            ]);
            if ($role == "user")
                DB::table('token_count')->where('user_id', $user->id)->incrementEach([
                    'daily_used' => 1,
                    'total_used' => 1
                ]);
            $res = new stdClass();
            $res->status = 1;
            $res->msg = "Create new chat and message successfully";
            return response()->json($res);
        } else {
            $res = new stdClass();
            $res->status = 0;
            $res->msg = "Chat not found";
            return response()->json($res);
        }
    }
    public function deleteMessage(Request $request)
    {
        $request->validate([
            'chat_id' => 'required',
            'index' => 'required'
        ]);

        $chat_id = $request->chat_id;
        $index = $request->index;
        try {
            // DB::raw("DELETE FROM message WHERE id = (SELECT id FROM (SELECT id FROM message WHERE chat_id = $chat_id LIMIT $index) AS temp ORDER BY id DESC LIMIT 1)");

            // return response()->json(DB::table('message')
            //     ->where('id', '=', function ($query) use ($chat_id, $index) {
            //         $query->select('id')->fromRaw("SELECT id FROM (SELECT id FROM message WHERE chat_id = $chat_id LIMIT $index) AS temp ORDER BY id DESC LIMIT 1");
            //     })
            //     ->get());

            DB::table('message')
                ->where('id', function ($query) use ($chat_id, $index) {
                    $query->select('id')
                        ->from('message')
                        ->where('chat_id', $chat_id)
                        ->offset($index)
                        ->limit(1);
                })
                ->delete();

            $res = new stdClass();
            $res->status = 1;
            $res->msg = "Delete message successfully";
            return response()->json($res);
        } catch (QueryException $ex) {
            $res = new stdClass();
            $res->status = 0;
            $res->msg = "Message not found: " . $ex;
            return response()->json($res);
        }
    }

}