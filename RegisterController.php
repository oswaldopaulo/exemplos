<?php



namespace App\Http\Controllers\Auth;



use App\User;

use App\VerifyUser;

use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Validator;

use Illuminate\Foundation\Auth\RegistersUsers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyMail;



class RegisterController extends Controller

{

    /*

     |--------------------------------------------------------------------------

     | Register Controller

     |--------------------------------------------------------------------------

     |

     | This controller handles the registration of new users as well as their

     | validation and creation. By default this controller uses a trait to

     | provide this functionality without requiring any additional code.

     |

     */

    

    use RegistersUsers;

    

    /**

     * Where to redirect users after registration.

     *

     * @var string

     */

    protected $redirectTo = '/';

    

    /**

     * Create a new controller instance.

     *

     * @return void

     */

    public function __construct()

    {

        $this->middleware('guest');

    }

    

    /**

     * Get a validator for an incoming registration request.

     *

     * @param  array  $data

     * @return \Illuminate\Contracts\Validation\Validator

     */

    protected function validator(array $data)

    {

        $data['cpf'] = str_replace(array(',','-','.'),'',$data['cpf']);
        
        $messages = [
            'gresponse.required' => 'Você é robô?',
            'gresponse.min' => 'Você é robô?',
        ];
        return Validator::make($data, [

            'name' => 'required|string|max:255',

            'email' => 'required|string|email|max:255|unique:usuarios',

            'password' => 'required|string|min:6|confirmed',
            'telefone' => 'required',
            'dat_nasc' => 'required|date',
            'cpf' => 'required|max:20|unique:usuarios',
            'cep' => 'required|max:10',
            'gresponse' => 'required|min:3',

        ], $messages);

    }

   

    /**

     * Create a new user instance after a valid registration.

     *

     * @param  array  $data

     * @return \App\User

     */

    protected function create(array $data)

    {

      $data['cpf'] = str_replace(array(',','-','.'),'',$data['cpf']);
      $data['cep'] = str_replace(array(',','-','.'),'',$data['cep']);
      
        $user = User::create([

            'name' => $data['name'],

            'email' => $data['email'],

            'password' => bcrypt($data['password']),
            'telefone' => $data['telefone'],
            'dat_nasc' => $data['dat_nasc'],
            'cpf' => $data['cpf'],
            'rua'=>$data['rua'],
            'numero'=>$data['numero'],
            'bairro'=>$data['bairro'],
            'cidade'=>$data['cidade'],
            'uf'=>$data['uf'],
            'cep'=>$data['cep'],
            'marketing'=>$data['receber_ofertas'],
            'termo'=>$data['termos'],
            
            
            

            

        ]);

        

        $verifyUser = VerifyUser::create([

            'user_id' => $user->id,

            'token' => str_random(40)

        ]);

        
        try{
          Mail::to($user->email)->send(new VerifyMail($user));

        } catch (\Swift_TransportException $e){
            
        }

        return $user;

        //return view('auth.verifyUser');

    }

    public function regjson(){
        
        $cpf =  str_replace(array('.','/','-'),'',$_POST['cpf']);
        
        $c = preg_replace('/\D/', '', $cpf);
        if (strlen($c) != 11 || preg_match("/^{$c[0]}{11}$/", $c)) {
            return response()->json(['error'=>['cpf' => 'CPF invalido']]);
        }
        for ($s = 10, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
        if ($c[9] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
            return response()->json(['error'=>['cpf' => 'CPF invalido']]);
        }
        
        for ($s = 11, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
        if ($c[10] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
            return response()->json(['error'=>['cpf' => 'CPF invalido']]);
        }
        
        $validator = $this->validator(Request::all());
        
        
        if ($validator->fails()) {
            // return response()->json($validator->messages(), 200);
            return response()->json(['error'=>$validator->messages()]);
        }
        
        
        $secretKey = '6Ld828oaAAAAAIrgrkhSkiQ5zBPslFy72J8swdpC';
        
        // See https://developers.google.com/recaptcha/docs/verify#api-request
        $fields = array(
            'secret'   => $secretKey,
            'response' => $_POST['gresponse']
        );
        
        $postVars = '';
        $sep = '';
        foreach ($fields as $key => $value) {
            $postVars .= $sep . urlencode($key) . '=' . urlencode($value);
            $sep = '&';
        }
        
        $ch = curl_init();
        
        curl_setopt($ch,CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $postVars);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
        
        $result = curl_exec($ch);
        
        curl_close($ch);
        
        $result = json_decode($result, true);
        
        if (!$result["success"]) return response()->json(['error'=>'2']);
        
        
        
          

            $user = $this->create(Request::all());
        
            if($user->id){
                return response()->json('success');
            } else {
                return response()->json(['error'=>"Erro desconhecido"]);
            }

           // return $this->registered($request, $user) ?: redirect($this->redirectPath());
            //return $this->registered(Request, $user) ?:response()->json('success') ;
        }

    public function verifyUser($token)

    {

        $verifyUser = VerifyUser::where('token', $token)->first();

        if(isset($verifyUser) ){

            $user = $verifyUser->user;

            if(!$user->verified) {

                $verifyUser->user->verified = 1;

                $verifyUser->user->save();

                $status = "Your e-mail is verified. You can now login.";

            }else{

                $status = "Your e-mail is already verified. You can now login.";

            }

        }else{

            return redirect('/login')->with('warning', "Sorry your email cannot be identified.");

        }

        

        return redirect('/login')->with('status', $status);

    }

    

    protected function registered()

    {

        $this->guard()->logout();

        return redirect('/login')->with('status', 'gameon.login_check_msn');

    }

    function resend(){
		
		$user = user::where('email',Request::input('email'))->first();
		Mail::to($user->email)->send(new VerifyMail($user));
		return redirect('/login')->with('status', 'gameon.login_check_msn');
		
	}

}

