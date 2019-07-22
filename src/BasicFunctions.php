<?php
/*
 * (c) Jainam Shah <jainam7480@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jainam;

use Auth;
use Hash;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Mail;

class BasicFunctions extends Controller
{
    //###############################################################
    //Function Name : checkEmailExists
    //Author : Jainam Shah <jainam@creolestudios.com>
    //Purpose : To check email exists in table or not
    //In Params : Email, ModalName
    //Return : Data of that user
    //###############################################################
    public function checkEmailExists($email, $modalName) {
        try {
            $modalName = '\App\\'.$modalName;
            if($modalName::where('email', $email)->exists()) {
                $data = $modalName::first();
                $returnData = UtilityController::Generateresponse(1, 'GENERAL_SUCCESS', Response::HTTP_OK, $data, 1);
            } else {
                $returnData = UtilityController::Generateresponse(0, 'EMPTY_MESSAGE', Response::HTTP_NO_CONTENT, '', 1);
            }
            return $returnData;
        }
        catch(\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            //Code to send mail ends here
            return UtilityController::Setreturnvariables();
        }
    }

    //###############################################################
    //Function Name : login
    //Author : Jainam Shah <jainam@creolestudios.com>
    //Purpose : To login and return user data
    //In Params : Email, Password
    //Return : Login user data
    //###############################################################
    public function login(Request $request) {
        try {
            $checkEmail = $this->checkEmailExists($request->email, 'User');
            if($checkEmail->status() == Response::HTTP_OK) {
                $data = json_decode($checkEmail->content())->data;
                if(Hash::check($request->password, $data->password)) {
                    $returnData = UtilityController::Generateresponse(1, 'GENERAL_SUCCESS', Response::HTTP_OK, $data, 1);
                } else {
                    $returnData = UtilityController::Generateresponse(0, 'GENERAL_ERROR', Response::HTTP_NO_CONTENT, '', 1);
                }
            } else {
                $returnData = UtilityController::Generateresponse(0, 'EMAIL_NOT_EXISTS', Response::HTTP_OK, '', 1);
            }
            return $returnData;
        }
        catch(\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            return UtilityController::Setreturnvariables();
        }
    }

    //###############################################################
    //Function Name : logout
    //Author : Jainam Shah <jainam@creolestudios.com>
    //Purpose : To logout user
    //In Params : N/A
    //Return : Redirect to view of home page
    //###############################################################
    public function logout() {
        try {
            $logout = !Auth::guard('admin')->logout();
            if($logout) {
                $returnData = UtilityController::Generateresponse(1, 'LOGOUT_SUCCESS', Response::HTTP_OK, 'LOGOUT_SUCCESS', 1);
            } else {
                $returnData = UtilityController::Generateresponse(1, 'GENERAL_ERROR', Response::HTTP_NO_CONTENT, 'GENERAL_ERROR', 1);
            }
            return view('auth.login');
        }
        catch(\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            //Code to send mail ends here
            return UtilityController::Setreturnvariables();
        }
    }

    //###############################################################
    //Function Name : changePassword
    //Author : Jainam Shah <jainam@creolestudios.com>
    //Purpose : To change password
    //In Params : N/A
    //Return : Redirect to view of home page
    //###############################################################
    public function changePassword($currentPwd, $newPwd, $modalName, $id = NULL) {
        try {
            DB::beginTransaction();
            $modalName = '\App\\'.$modalName;
            if($id) {
                $userData = $modalName::find($id)->first();  
            } else {
                $user = Auth::user();
                $userData = $ModalName::find($user->id)->first();
            }
            $checkPwd = Hash::check($currentPwd, $userData->password);
            if($checkPwd) {
                $userData->password = Hash::make($newPwd);
                $updatePassword = $userData->save();
                if($updatePassword) {
                    $returnData = UtilityController::Generateresponse(1, 'GENERAL_SUCCESS', Response::HTTP_OK, '', 1);
                } else {
                    $returnData = UtilityController::Generateresponse(0, 'GENERAL_ERROR', Response::HTTP_NO_CONTENT, '', 1);
                }
            } else {
                return UtilityController::Generateresponse(0, 'PASSWORD_MISMATCH', Response::HTTP_OK, '2', 1);
            }
            DB::commit();
            return $returnData;
        }
        catch(\Exception $e) {
            DB::rollback();
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            return UtilityController::Setreturnvariables();
        }
    }

    //###############################################################
    //Function Name : forgotPassword
    //Author : Jainam Shah <jainam@creolestudios.com>
    //Purpose : To send link to reset password
    //In Params : N/A
    //Return : Success or failure response
    //###############################################################
    public function forgotPassword(Request $request) {
        try {
            $viewFile = Config('constant.forgot_password.VIEW_FILE_EXCEPTION');
            $fromEmail = Config('constant.forgot_password.FROM_EMAIL');
            $fromEmailName = Config('constant.forgot_password.FROM_EMAIL_NAME');
            $recepients = Config('constant.send_mail_to');
            $mailSubject = Config('constant.forgot_password.EXCEPTION_MAIL_SUBJECT');

            $dataArray = array();
            $dataArray['name'] = 'Jainam';
            $dataArray['action_url'] = url('/');
            $dataArray['home_page'] = url('/');
            $dataArray['project_name'] = 'Project Name';
            
            $mail = Mail::send($viewFile, $dataArray, function ($message) use ($dataArray, $viewFile, $fromEmail, $fromEmailName, $recepients, $mailSubject) {
                $message->from($fromEmail, $fromEmailName);
                $message->to($recepients);
                $message->subject($mailSubject);
            });
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            return UtilityController::Setreturnvariables();
        }
    }
}
