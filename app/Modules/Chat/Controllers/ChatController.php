<?php namespace App\Modules\Chat\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Modules\Chat\Models\Chat;
use GuzzleHttp\Psr7\Response;
use LucaDegasperi\OAuth2Server\Facades\Authorizer;

use Illuminate\Http\Request;

use App\Repositories\ChatRepository;

class ChatController extends Controller
{

    protected $pubnub;

    public function __construct(/*ChatRepository $pubnub*/)
    {
//        $this->pubnub = $pubnub;
        // end user access
        $this->middleware(
            "oauth",
            [
                'only' =>
                    ['index', 'store']
            ]
        );
        $this->middleware(
            "oauth-user",
            [
                'only' =>
                    ['index', 'store']
            ]
        );
        // backend acces
        $this->middleware('auth',
            [
                'except' =>
                    ['index', 'store', 'insertLog', 'oauthUpdateChat']
            ]
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $ownerId = Authorizer::getResourceOwnerId();
        $chat = Chat::where('sender_id', $ownerId)
            ->orWhere('receiver_id', $ownerId)
            ->get();
        return response()->json(array(
            'status' => 200,
            'message' => 'success retrieve',
            'data' => $chat
        ), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        echo 'create';
        echo '<script>console.log("test")</script>';
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $param = array();
        $param['sender_id'] = Authorizer::getResourceOwnerId();
        foreach ($request->data as $key => $item) {
            $param[$key] = $item;
        }
        $chat = Chat::create($param);
        if ($chat) {
            $data = array("chat_id" => $chat["id"]);
            return response()->json(array(
                'status' => 201,
                'message' => 'Success Saving',
                'data' => $data
            ), 201);
        } else {
            return response()->json(array(
                'status' => 500,
                'message' => 'Error Saving'
            ), 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id, Request $request)
    {
        echo '<pre>';
        print_r($id);
//        dd($id);
//        $this->fnUpdateChat($id,$request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Insert into chat logs from user dashboard (operator/admin)
     *
     * @param Request $request
     */
    public function insertLog(Request $request)
    {
        /**
         * sender id,
         * receiver id,
         * message,
         * ip address
         * useragent
         * read,
         * created at
         * updated at
         */
        $return = [];

//        $ownerId =  Authorizer::getResourceOwnerId();

        if (is_null($request->sender_id)) {
            $return['status'] = 404;
            $return['message'] = 'not found';
        } else {
            $chat = Chat::create([
                'sender_id' => $request->sender_id,
                'receiver_id' => $request->receiver_id,
                'message' => $request->message,
                'ip_address' => $request->ip_address,
                'useragent' => $request->useragent,
//                'read' => $request->read
            ]);

            if ($chat) {
                $return['status'] = 201;
                $return['message'] = 'success';
                $return['data'] = $chat;
            } else {
                $return['status'] = 500;
                $return['message'] = 'error';
            }
        }

        return response()->json($return);
        // get user login
        #echo var_dump(auth()->user());
    }

    public function chatLog($id, Response $response)
    {
        $ownerId = auth()->user()->id;
        $chat = Chat::whereRaw('((sender_id = ' . $ownerId . ' or receiver_id = ' . $ownerId . ') and (sender_id = ' . $id . ' or receiver_id = ' . $id . '))')
            ->get();
        return response()->json(array(
            'status' => 200,
            'message' => 'success retrieve',
            'data' => $chat
        ), 200);
    }

    //===================== PUBNUB PROSES =====================
    /**
     * grant channel group to be accessed
     * @param Request $request
     * @return mixed
     */
    public function grantChannelGroup(Request $request)
    {
        // _set magic method (ex: role = users)
        $this->pubnub->channelGroup = "cg-" . $request->role;
        // _get magic method
        $response = $this->pubnub->grantChannelGroup;
        // $data['token'] = csrf_token();
        return $this->responseChannelRequest($response);
    }

    /**
     * add user channel to specific group channel
     * @param Request $request
     * @return mixed
     */
    public function addChannelToGroup(Request $request)
    {
        // _set magic method (ex: role = users)
        $this->pubnub->channelGroup = "cg-" . $request->role;
        // set static variabel channel on chat repository to $request->channel
        $this->pubnub->channel = $request->channel;
        // response action using _get magic method
        $response = $this->pubnub->groupAddChannel();
        return $this->responseChannelRequest($response);
    }

    /**
     * remove user channel from group channel
     * @param Request $request
     * @return mixed
     */
    public function removeChannelFromGroup(Request $request)
    {
        // _set magic method (ex: role = users)
        $this->pubnub->channelGroup = "cg-" . $request->role;
        // set static variabel channel on chat repository to $request->channel
        $this->pubnub->channel = $request->channel;
        // response action using _get magic method
        $response = $this->pubnub->groupRemoveChannel();
        return $this->responseChannelRequest($response);
    }

    /**
     * used to return response from pubnub proses
     * @param $response
     * @return mixed
     */
    private function responseChannelRequest($response)
    {
        $code = $response['status'];
        return response()->json(array(
            'status' => $code,
            'message' => $response['message'] . "-" . $response['service'],
            'error' => $response['error']
        ), $code);
    }
    //=================== END PUBNUB PROSES ===================

    //================== UPDATE FUNCTION ===================

    /**
     * Used for backend proses (need auth middleware)
     * @param $id, chats id
     * @param Request $request, data to used to update chats table
     */
    public function authUpdateChat($id, Request $request)
    {
        $this->fnUpdateChat($id, $request);
    }

    /**
     * Used for api proses (need oauth & oauth-users middleware)
     * @param $id, chats id
     * @param Request $request, data to used to update chats table
     */
    public function oauthUpdateChat($id, Request $request)
    {
        $ownerId = Authorizer::getResourceOwnerId();
        if (!($ownerId === nullOrEmptyString())) {
            $this->fnUpdateChat($id, $request->data);
        } else {
            return response()->json(array(
                'status' => 404,
                'message' => 'Sorry, you don\'t have access'
            ), 404);
        }
    }

    /**
     * @param $id, id message to be updated
     * @param $data, used to update chats table
     * @return mixed
     */
    protected function fnUpdateChat($id, $data)
    {
        dd($data);

        // jika id dan data valid
        if ($this->valueValidation($id) && $this->valueValidation($data)) {

            // get chat message by id
            $chat = Chat::find($id);
            dd($chat);

            // chat message defined by id is null
            if (is_null($chat)) {
                return response()->json(array(
                    'status' => 404,
                    'message' => 'Data Not Found'
                ), 404);
            }

            // action must be set
            if (!$this->valueValidation($data['action'])) {
                return response()->json(array(
                    'status' => 404,
                    'message' => 'Action is not defined'
                ), 404);
            }

            // handle data->action
            // 1: update chat message when user serviced at the first time by operator
            // 2: just update whatever you need
            switch ($data['action']) {
                case 1:
                    $return = $this->updateProcess($chat, $data);
                    break;
                default:
                    if (!($chat->receiver_id == nullOrEmptyString())) {
                        $return = $this->updateProcess($chat, $data);
                    } else {
                        if ($chat->receiver_id != auth()->user()->id) {
                            // if operator trying to handle users that still serviced by other operator
                            $status = 500;
                            $message = 'Handled by others';
                        } else {
                            // show notif, this is the owner
                            $status = 201;
                            $message = 'You are the owner';

                        }

                        // return to be parse as json
                        $return = array(
                            "status" => $status,
                            "message" => $message
                        );
                    }
                    break;
            }

            // return update process
            return response()->json($return, 200);
        }
    }

    /**
     * This function used for update dynamically in chats table
     * @param $chat, message object from chat table defined by id
     * @param $data, used to be parameter and value to be updated
     * @return array
     */
    protected function updateProcess($chat, $data)
    {
        // pop the action key from data array
        $data = array_except($data,'action');

        // set chat field and value to be updated
        foreach ($data as $key => $item) {
            $chat->$key = $item;
        }

        // update chat
        $success = $chat->save();

        if ($success) {
            $status = 201;
            $message = 'Success Updating';
        } else {
            $status = 500;
            $message = 'Error Updating';
        }

        return array(
            "status" => $status,
            "message" => $message
        );
    }

    //================ END UPDATE FUNCTION =================

    //================ HISTORY FUNCTION ================

    /**
     * fungsi utk retrieve unseen chat di sisi backend
     * @param $id
     * @return mixed
     */
    public function authHistory($read) {
        $unseenPrivate = $this->fnHistory($read,auth()->user()->id);
        // $unseenPublic = $this->fnHistory(0,"");
        // $data = $unseenPrivate->merge($unseenPublic);
        return response()->json(array(
            'status' => 200,
            'message' => 'success retrieve',
            'data' => $unseenPrivate
        ), 200);
    }

    /**
     * FUngsi untuk menampilkan history notification where date between now and now - 2
     * @param $data
     * @param $read, true if seen message and false if unseen message
     * @return mixed
     */
    protected function fnHistory($read,$user) {
        if ($read == 1) {
            $seenStatus = 'is not null';
        } else {
            $seenStatus = 'is null';
        }
        if ($user == "") {
            $user = "is null";
        } else {
            $user = "= 3";
        }
        $chat = Chat::join('users','users.id','=','chats.sender_id')
            ->whereRaw('chats.receiver_id ' . $user. ' and chats.read '.$seenStatus.' and date(chats.created_at) > date(now())-integer \'3\'')
            ->orderBy('chats.sender_id', 'message_id','desc')
            ->selectRaw('
            distinct on (chats.sender_id) chats.sender_id,
            chats.id as message_id,
            chats.receiver_id,
            chats.message,
            chats.read,
            chats.created_at,
            users.id as user_id,
            users.channel as sender_channel,
            users.name,
            case
             when users.firstname is null
             then
               users.name
             else
               users.firstname
             end as user')
            ->get();
        // debug query purpose
//            ->toSql();
//        dd($chat);


        return $chat;
    }
    //============== END HISTORY FUNCTION ===============


    //================ CUSTOM FUNCTION =================
    /**
     * @param $data, to be validate is empty or not is null or not, etc
     * @return bool, true for valid and false for not valid
     */
    protected function valueValidation($data)
    {
        if (is_array($data) && !($data == emptyArray())) {
            return true;
        } else {
            if (is_string($data) && !($data == isEmptyOrNullString())) {
                return true;
            } else {
                return false;
            }
        }
    }
    //============== END CUSTOM FUNCTION ===============


}
