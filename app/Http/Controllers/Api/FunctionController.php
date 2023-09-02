<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Config;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use stdClass;

ini_set('max_execution_time', '600');

class FunctionController extends Controller
{
    public function txt2img(Request $request)
    {
        $request->validate([
            'chat_id' => 'required',
            'role' => 'required',
            'content' => 'required',
            'ratio_x' => 'required',
            'ratio_y' => 'required'
        ]);

        $user = Auth::user();

        $prompt = "masterpiece, best quality, very detailed, high resolution, sharp, sharp image, 4k, 8k, " . mb_strtolower($request->content);
        $ratio_x = $request->ratio_x;
        $ratio_y = $request->ratio_y;
        $negative_prompt = '((nsfw)), (deformed iris, deformed pupils), text, worst quality, low quality, jpeg artifacts, ugly, (ugly face, deformed face), duplicate, morbid, mutilated, (extra fingers), (mutated hands), ((poorly drawn hands)), ((poorly drawn feet)), ((poorly drawn face)), mutation, deformed, blurry, dehydrated, bad anatomy, bad proportions, extra limbs, cloned face, disfigured, gross proportions, malformed limbs, missing arms, missing legs, extra arms, extra legs, (fused fingers), (too many fingers), long neck, camera, watermark and signature, out of frame';
        $steps = 50;

        $width = $ratio_x > $ratio_y ? $ratio_x / $ratio_y * 512 : 512;
        $height = $ratio_x > $ratio_y ? 512 : $ratio_y / $ratio_x * 512;

        // $res = new stdClass();
        // $res->status = 2;
        // $res->msg = "Pending";
        // $res->priority = 4;
        // $res->total = 5;
        // return response()->json($res);

        $response_txt2img = Http::timeout(120)->post(Cache::get('base_url', 'http://127.0.0.1:7860') . config('app.stable_diffusion.txt2img'), [
            'prompt' => $prompt,
            'negative_prompt' => $negative_prompt,
            'steps' => $steps,
            'width' => $width,
            'height' => $height,
            'restore_faces' => true,
            'sampler_name' => 'Euler',
            'callback_url' => url('/api/image/upload')
        ]);

        if ($response_txt2img->successful()) {
            // Request was successful, handle the response
            $responseData = $response_txt2img->json();
            // Process the response data
            // $image = new stdClass();
            // $image->base64 = "data:image/png;base64, " . $responseData["images"][0];

            $task_id = $responseData['task_id'];

            DB::table('image')->insert([
                'task_id' => $task_id,
                'user_id' => $user->id
            ]);

            $response_queue = Http::timeout(120)->get(Cache::get('base_url', 'http://127.0.0.1:7860') . config('app.stable_diffusion.queue_status'));

            if ($response_queue->successful()) {
                $responseData = $response_queue->json();
                $current_task_id = $responseData['current_task_id'];

                $res = new stdClass();
                if ($current_task_id == $task_id) {
                    $res->status = 1;
                    $res->msg = "Running";
                    $res->task_id = $task_id;
                    return response()->json($res);
                }

                $pending_tasks = $responseData['pending_tasks'] ?? [];

                foreach ($pending_tasks as $key => $pending_task) {
                    if ($pending_task["id"] == $task_id) {
                        $res->status = 2;
                        $res->msg = "Pending";
                        $res->task_id = $task_id;
                        $res->priority = $key + 1;
                        $res->total = sizeof($pending_tasks);
                        return response()->json($res);
                    }
                }
            } else {
                // Request failed, handle the error
                $statusCode = $response_queue->status();
                $errorData = $response_queue->json();
                // Process the error data
                return response()->json($errorData, 500);
            }

            $res->status = 0;
            $res->msg = "Empty";
            return response()->json($res);
        } else {
            // Request failed, handle the error
            $statusCode = $response_txt2img->status();
            $errorData = $response_txt2img->json();
            // Process the error data
            return response()->json($errorData, 500);
        }
    }
    public function queueStatus(Request $request)
    {
        $request->validate([
            'task_id' => 'required'
        ]);

        $task_id = $request->task_id;

        // $res = new stdClass();
        // $res->status = 2;
        // $res->msg = "Pending";
        // $res->priority = 2;
        // $res->total = 3;
        // return response()->json($res);

        $response = Http::timeout(120)->get(Cache::get('base_url', 'http://127.0.0.1:7860') . config('app.stable_diffusion.queue_status'));

        if ($response->successful()) {
            $responseData = $response->json();
            $current_task_id = $responseData["current_task_id"];

            $res = new stdClass();
            if ($current_task_id == $task_id) {
                $res->status = 1;
                $res->msg = "Running";
                return response()->json($res);
            }

            $pending_tasks = $responseData["pending_tasks"] ?? [];

            foreach ($pending_tasks as $key => $pending_task) {
                if ($pending_task["id"] == $task_id) {
                    $res->status = 2;
                    $res->msg = "Pending";
                    $res->priority = $key + 1;
                    $res->total = sizeof($pending_tasks);
                    return response()->json($res);
                }
            }

            $res->status = -1;
            $res->msg = "Completed";
            $res->task_id = $task_id;
            return response()->json($res);

            // $res->status = 0;
            // $res->msg = "Empty";
            // return response()->json($res);
        } else {
            // Request failed, handle the error
            $statusCode = $response->status();
            $errorData = $response->json();
            // Process the error data
            return response()->json($errorData, 500);
        }
    }

    // public function mediaPlayer(Request $request)
    // {
    //     $request->validate([
    //         'prompt' => 'required'
    //     ]);

    //     try {
    //         $prompt = new stdClass();
    //         $prompt->model = config('app.chat_gpt.model', 'gpt-3.5-turbo');
    //         $messages = array();
    //         $ex_msg = new stdClass();
    //         $ex_msg->role = "user";
    //         $ex_msg->content = "Liệt kê một số bài hát theo đúng chủ đề: nhẹ nhàng, v-pop; thành 1 dòng duy nhất, ngăn cách bởi dấu phẩy, không bao gồm đánh số thứ tự (1, 2, 3) và tên ca sĩ";
    //         array_push($messages, $ex_msg);
    //         $ex_msg = new stdClass();
    //         $ex_msg->role = "assistant";
    //         $ex_msg->content = "Em của ngày hôm qua, Bình yên nơi đâu, Một bước yêu vạn dặm đau, Hoa nở không màu, Điều anh biết, Có chắc yêu là đây, Đừng hỏi em, Sai người sai thời điểm";
    //         array_push($messages, $ex_msg);
    //         $ex_msg = new stdClass();
    //         $ex_msg->role = "user";
    //         $ex_msg->content = "Liệt kê một số bài hát theo đúng chủ đề: sôi động, us-uk; thành 1 dòng duy nhất, ngăn cách bởi dấu phẩy, không bao gồm đánh số thứ tự (1, 2, 3) và tên ca sĩ";
    //         array_push($messages, $ex_msg);
    //         $ex_msg = new stdClass();
    //         $ex_msg->role = "assistant";
    //         $ex_msg->content = "Uptown Funk, Party Rock Anthem, Can't Stop the Feeling, Don't Stop Believin', We Will Rock You, Waka Waka, Hey Ya!, Dancing Queen, I Wanna Dance with Somebody, Dynamite, Shake It Off";
    //         array_push($messages, $ex_msg);
    //         $ex_msg = new stdClass();
    //         $ex_msg->role = "user";
    //         $ex_msg->content = "Liệt kê một số bài hát theo đúng chủ đề: $request->prompt; thành 1 dòng duy nhất, ngăn cách bởi dấu phẩy, không bao gồm đánh số thứ tự (1, 2, 3) và tên ca sĩ";
    //         array_push($messages, $ex_msg);
    //         $prompt->messages = $messages;
    //         // $prompt->max_token = 500;
    //         $prompt->temperature = 0;
    //         $prompt->stream = false;
    //         if (empty(config('app.chat_gpt.api_key'))) {
    //             $response = Http::retry(3, 15)
    //                 ->withBody(json_encode($prompt), 'application/json')
    //                 ->post(config('app.chat_gpt.base_url', ''));
    //         } else {
    //             $response = Http::retry(3, 15)
    //                 ->withHeaders([
    //                     'Authorization' => 'Bearer ' . config('app.chat_gpt.api_key', '')
    //                 ])
    //                 ->withBody(json_encode($prompt), 'application/json')
    //                 ->post(config('app.chat_gpt.base_url', ''));
    //         }

    //         $response->onError(function () {
    //             return response()->json("Error: " . config('app.chat_gpt.base_url', ''), 200);
    //         });
    //         if ($response->successful() && !empty($response->json("choices"))) {
    //             $content = $response->json("choices")[0]["message"]["content"];
    //             $media_playlist = explode(",", $content);
    //             $media_playlist = array_map('trim', $media_playlist);

    //             $media_links = array();
    //             foreach ($media_playlist as $media) {
    //                 $fetch = Http::retry(2, 5)->timeout(5)
    //                     ->get(config("app.nodejs_server.base_url", '') . "/search-first?name=$media");
    //                 $fetch->onError(function () {
    //                     return response()->json("Error: " . config('app.chat_gpt.base_url', ''), 200);
    //                 });
    //                 if ($fetch->successful() && !empty($fetch->json("streamUrl"))) {
    //                     array_push($media_links, url('/') . '/api/audio?url=' . urlencode($fetch->json("streamUrl")));
    //                 }
    //             }
    //             $playlist = new stdClass();
    //             $playlist->type = "media";
    //             $playlist->genres = $request->prompt;
    //             $playlist->media_links = $media_playlist;
    //             return response()->json($playlist, 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    //             $playlist = new stdClass();
    //             $playlist->type = "media";
    //             $playlist->genres = $request->prompt;
    //             $playlist->songs = $media_playlist;
    //             $media_links = array();
    //             foreach ($media_playlist as $media_name) {
    //                 $fetch = Http::timeout(2)->get(config('app.zing_mp3.search_url', '') . $media_name);
    //                 $fetch->onError(function () {
    //                     return response()->json("Error: " . config('app.zing_mp3.search_url', ''), 200);
    //                 });
    //                 if ($fetch->successful()) {
    //                     // return response()->json($response->json("data")[0]["song"]["0"]["id"]);
    //                     if (!empty($response->json("data")[0]["song"]) && !empty($response->json("data")[0]["song"]["0"])) {
    //                         $id = $response->json("data")[0]["song"]["0"]["id"];
    //                         array_push($media_links, url('/') . '/api/audio?url=' . config('app.zing_mp3.audio_url', '') . $id . '/128');
    //                     }
    //                 } else {
    //                     return response()->json("No media found", 204);
    //                 }
    //             }
    //             $playlist->media_links = $media_links;
    //             return response()->json($playlist, 200, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    //         } else {
    //             return response()->json("No media found: " . config('app.chat_gpt.base_url', '') . json_encode($response->json()), 200);
    //         }
    //     } catch (Exception $ex) {
    //         return response()->json("Error: " . $ex, 200);
    //     }
    // }
}