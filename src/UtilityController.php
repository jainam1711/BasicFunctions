<?php

/**
 * Short description for file
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Utility Components
 * @package    UtilityController
 * @author     Raiser
 * @copyright  2017 Raiser
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Git Branch: used in all branch, basically contains generic functions
 * @since      File available since Release 1.0.0
 * @deprecated N/A
 */
/*
 * Place includes controller for project module.
 */

/**
Pre-Load all necessary library & name space
 */

namespace Jainam;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Validator;

// Load models

class UtilityController extends BaseController
{

    //###############################################################
    //Function Name : Sendexceptionmail
    //Author : Raiser
    //Purpose : To send the mail when any Exception is catched
    //In Params : Exception object only. (From controller)
    //Return : 'true' when email will be sent successfully.
    //###############################################################
    public static function Sendexceptionmail($object) {
        try {
            $viewFile = Config('constant.config_variables.VIEW_FILE_EXCEPTION');
            $fromEmail = Config('constant.config_variables.FROM_EMAIL');
            $fromEmailName = Config('constant.config_variables.FROM_EMAIL_NAME');
            $recepients = Config('constant.send_mail_to');
            $mailSubject = Config('constant.config_variables.EXCEPTION_MAIL_SUBJECT');

            $dataArray = array();
            $dataArray['Error'] = $object->getMessage();
            $dataArray['File'] = $object->getFile();
            $dataArray['Line'] = $object->getLine();
            $dataArray['Source'] = isset($_SERVER['SYSTEM_NAME']) ? $_SERVER['SYSTEM_NAME'] : '';

            //Check Condition if not run from local server
            if (strpos(url('/'), 'localhost') == true) {
                $mail = Mail::send($viewFile, $dataArray, function ($message) use ($dataArray, $viewFile, $fromEmail, $fromEmailName, $recepients, $mailSubject) {
                        $message->from($fromEmail, $fromEmailName);
                        $message->to($recepients);
                        $message->subject($mailSubject);
                    });
            }
        } catch (\Exception $e) {
            print_r($e->getMessage());
            die;
        }
        return true;
    }


    //###############################################################
    //Function Name : Imageexist
    //Author : ketan Solanki <ketan@creolestudios.com>
    //Purpose : TO Check whether the image exists or not
    //In Params : File name and physical path and Url path and placeholder image path
    //Return : If Image exists then full path else placeholder image path
    //###############################################################
    public static function Imageexist($imageName, $path, $urlPath, $placeHolderPath)
    {
        $fileName         = $urlPath . $imageName;
        $physicalFileName = $path . $imageName;
        if (file_exists($physicalFileName) && $imageName != '') {
            $fileName = $fileName;
        } else {
            $fileName = $placeHolderPath;
        }
        return $fileName;
    }

    //###############################################################
    //Function Name : _group_by
    //Author : ashish patel (ashish@creolestudios.com)
    //Purpose :to groupby elements by key
    //In Params : array
    //Return : array
    //###############################################################
    public static function _group_by($array, $key)
    {
        $return = array();
        foreach ($array as $val) {
            $return[$val[$key]][] = $val;
        }
        return $return;
    }

    //###############################################################
    //Function Name : Setreturnvariables
    //Author : Raiser
    //Purpose : To initialize the return variables
    //In Params : N/A
    //Return : Array of return response
    //###############################################################
    public static function Setreturnvariables($isObject = 0)
    {
        $returnData                = array();
        $object                    = collect();
        $returnData['status']      = Config('constant.standard_response_values.FAILURE');
        $returnData['message']     = Config('constant.messages.GENERAL_ERROR');
        $returnData['status_code'] = Response::HTTP_BAD_REQUEST;
        $returnData['data']        = $isObject ? (object) [] : [];

        return response()->json($returnData, Response::HTTP_BAD_REQUEST);
    }

    //###############################################################
    //Function Name : Generateresponse
    //Author : Raiser
    //Purpose : To initialize the return variables
    //In Params : N/A
    //Return : Array of return response
    //###############################################################
    public static function Generateresponse($type, $message, $statusCode, $responseData = array(), $withJson = 0)
    {
        $returnData = array();

        $returnData['status'] = ($type && $type == 1) ? Config('constant.standard_response_values.SUCCESS') : (($type && $type == 2) ? Config('constant.standard_response_values.WARNING') : Config('constant.standard_response_values.FAILURE'));

        if (Config('constant.messages.' . $message) != '') {
            $returnData['message'] = Config('constant.messages.' . $message);
        } else {
            $returnData['message'] = $message;
        }
        $returnData['status_code'] = $statusCode;
        $returnData['data']        = ($responseData != '') ? $responseData : (object) [];

        if ($withJson) {
            return response($returnData, $statusCode);
        }
        return $returnData;

    }

    //###############################################################
    //Function Name : ValidationRules
    //Author : Ashish Patel(ashish@creolestudios.com)
    //Purpose : to validate request fields
    //In Params : N/A
    //Return : Array of return response
    //###############################################################

    public static function ValidationRules($requests, $modelClassName, $unsets = '')
    {
        try {
            //  assigned default response
            $returnData = UtilityController::Setreturnvariables();
            if (!empty($modelClassName)) {
                if (empty($requests)) {
                    return UtilityController::Generateresponse(0, 'EMPTY_REQUEST', Response::HTTP_BAD_REQUEST, '');
                }
                $modelClassName  = 'App\\' . $modelClassName; //  To make model object using string variable, use full reference of model class
                $$modelClassName = new $modelClassName;
                if ($$modelClassName != '') {
                    $rules = $$modelClassName->rules; // get rules from spacific model
                    if (empty($rules)) {
                        return UtilityController::Generateresponse(0, 'NO_RULES', Response::HTTP_BAD_REQUEST, '');
                    }
                    if (!empty($unsets) && is_array($unsets)) {
                        // if $unset is not empty than unset those fields from rule
                        /* unset rules which are not in use*/
                        foreach ($unsets as $key => $value) {
                            unset($rules[$value]);
                        }
                    }
                    // validate request
                    $validator = Validator::make($requests, $rules);
                    if ($validator->fails()) {
                        $messages   = implode(" & ", $validator->messages()->all());
                        $returnData = UtilityController::Generateresponse(0, $messages, Response::HTTP_BAD_REQUEST, '');
                    } else {
                        $returnData = UtilityController::Generateresponse(1, 'EMPTY_MESSAGE', Response::HTTP_OK, '');
                    }
                }
            }

            return $returnData;
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            $returnData        = UtilityController::Generateresponse(0, $e->getMessage(), Response::HTTP_BAD_REQUEST, '');
            return $returnData;
        }

    }
    //###############################################################
    //Function Name : Createorupdate
    //Author : Ashish Patel <ashish@creolestudios.com>
    //Purpose : to create or update data
    //In Params : void
    //Date : 8th March 2018
    //###############################################################

    public static function Createorupdate($data, $modelClassName)
    {
        try {
            $modelClassName = 'App\\' . $modelClassName; //  To make model object using string variable, use full reference of
            if ($modelClassName && !empty($data)) {
                $id = isset($data['id']) && $data['id'] != '' ? $data['id'] : '';
                if (isset($data['id'])) {
                    unset($data['id']);
                }
                $createOrUpdate = $modelClassName::updateOrCreate(['id' => $id], $data);
                return $createOrUpdate;
            } else {
                //  Else return false to controller.
                return false;
            }
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            //Code to send mail ends here
            return response()->json(UtilityController::Setreturnvariables());
        }

    }

    public static function Createupdatearray($data, $model)
    {
        try {
            if ($data && $model) {
                $returnData = new Collection();
                foreach ($data as $key => $value) {
                    $returnData->push(UtilityController::Createorupdate($value, $model));
                }
                return $returnData;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            //Code to send mail ends here
            return response()->json(UtilityController::Setreturnvariables());
        }
    }

    //###############################################################
   //Function Name : Makemodelobject
   //Author : Raiser
   //Purpose : Generic function to create model object to save()
   //Return : model object
   //###############################################################
   public static function Makemodelobject($data, $modelClassName, $primaryKey = 'id', $idToEdit = '')
   {
       try {
           if (!empty($data) && $modelClassName != '') {
               $modelClassName = 'App\\' . $modelClassName; //  To make model object using string variable, use full reference of model class
               if ($idToEdit == '') {
                   //  If id is passed to edit the record. (Update function)
                   $$modelClassName = new $modelClassName;
               } else {
                   $$modelClassName = $modelClassName::find($idToEdit);
               }
               if ($$modelClassName != '') {
                   //  If object created then proceed.
                   // Getting all columns of the table.
                   $columns = Schema::getColumnListing($$modelClassName->getTable());

                   //  Looping through all the table columns to check all input values in $data variable.
                   foreach ($columns as $k => $v) {
                       if (array_key_exists($v, $data)) {
                           $$modelClassName->$v = $data[$v];
                       }
                   }
                   if ($$modelClassName->save()) {
                       return $$modelClassName;
                   } else {
                       return false;
                   }
               } else {
                   //  Else return false to controller.
                   return false;
               }
           }
       } catch (\Exception $e) {
           $sendExceptionMail = UtilityController::Sendexceptionmail($e);
           $returnData        = UtilityController::Generateresponse(0, $e->getMessage(), Response::HTTP_BAD_REQUEST, '');
           return $returnData;
       }
   }
    //###############################################################
    //Function Name : Savearray
    //Author : Ashish Patel <ashish@creolestudios.com>
    //Purpose : used same Makemodelobject method just to save an array
    //In Params : void
    //Date : 3rd January 2018
    //Return: model object
    //###############################################################

    public static function Savearray($data = array(), $modelClassName)
    {
        try {
            $returnData = [];
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $idToEdit     = isset($value['id']) && $value['id'] ? $value['id'] : '';
                    $returnData[] = UtilityController::Makemodelobject($value, $modelClassName, 'id', $idToEdit);
                }
            }

            return $returnData;
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            //Code to send mail ends here
            return response()->json(UtilityController::Setreturnvariables());
        }

    }

    //###############################################################
    //Function Name : Savearrayobject
    //Author : Ashish Patel <ashish@creolestudios.com>
    //Purpose : Save array and get objects in response
    //In Params : void
    //Date : 29th January 2018
    //Return: model object
    //###############################################################

    public static function Savearrayobject($data = array(), $modelClassName)
    {
        try {
            $returnData = new Collection();
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $idToEdit = isset($value['id']) && $value['id'] ? $value['id'] : '';
                    $returnData->push(UtilityController::Makemodelobject($value, $modelClassName, 'id', $idToEdit));
                }
            }
            return $returnData;
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            //Code to send mail ends here
            return response()->json(UtilityController::Setreturnvariables());
        }

    }

    //###############################################################
    //Function Name : MakeArrayFromInput
    //Author : Raiser
    //Purpose : Generic function to create array from input parameters
    //Return : model object
    //###############################################################
    public static function MakeObjectFromArray($data, $modelClassName)
    {
        try {
            if (isset($data) && !empty($data)) {
                $modelClassName = 'App\\' . $modelClassName; //  To make model object using string variable, use full reference of model class
                //define model with reference
                $$modelClassName = new $modelClassName;
                if ($$modelClassName != '') {
                    //  If object created then proceed.
                    // Getting all columns of the table.
                    $columns = Schema::getColumnListing($$modelClassName->getTable());
                    //  Looping through all the table columns to check all input values in $data variable.
                    foreach ($columns as $k => $v) {
                        if (isset($data[$v])) {
                            $$modelClassName->$v = $data[$v];
                        }

                    }
                    return $$modelClassName;
                } else {
                    //  Else return false to controller.
                    return false;
                }
            }
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            $returnData        = UtilityController::Generateresponse(0, $e->getMessage(), Response::HTTP_BAD_REQUEST, '');
            return $returnData;
        }
    }

    //###############################################################
    //Function Name : Custompaginate
    //Author : Ashish Patel <ashish@creolestudios.com>
    //Purpose : to paginate custom
    //In Params : void
    //Date : 31th January 2018
    //###############################################################

    public static function Custompaginate($items, $perPage = 10, $page = null, $options = [])
    {
        $page  = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, $options);
    }

    public static function Getfilename($file)
    {
        if (!empty($file)) {
            $extention = $file->guessClientExtension();
            $append    = rand(0, 999999);
            $fileName  = Carbon::now()->timestamp . $append . '.' . $extention;

            return $fileName;
        }
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | ExceptionHandler
    |--------------------------------------------------------------------------
    |
    | Author    : Ashish Patel <ashish@creolestudios.com>
    | Purpose   : to mail app side exception
    | In Params : void
    | Date      : 21st August 2018
    |
     */

    public function ExceptionHandler(Request $request)
    {
        try {
            $returnData = UtilityController::Generateresponse(1, 'EMPTY_MESSAGE', Response::HTTP_OK, '', 1);
            $data       = $request->information;
            $mail       = Mail::send('emails.appexception', ['data' => $data], function ($message) {
                $message->from('exception@modtod.com', 'ModTod Exception');
                $message->to('nirmalsinh@creolestudios.com');
                $message->cc('dhara@creolestudios.com');
                $message->bcc('ashish@creolestudios.com');
                $message->subject('App side Exception');
            });
            return $returnData;
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            //Code to send mail ends here
            return UtilityController::Setreturnvariables();
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Commonconfugirations
    |--------------------------------------------------------------------------
    |
    | Author    : Ashish Patel <ashish@creolestudios.com>
    | Purpose   : to get common confugirations
    | In Params : vpopmail_del_domain(domain)
    | Date      : 9th August 2018
    |
     */

    public function Commonconfugirations()
    {
        try {
            $responseData                      = [];
            $responseData['termscondition']    = url('/common/tearmsCondition');
            $responseData['modtod_commission'] = Config('constant.common_variables.MODTOD_COMMISION');
            $responseData['links']             = \App\UserLinks::first();
            $responseData['common_messages']   = \App\CommonMessage::get()->toArray();
            $responseData['filter']            = \App\Attributes::with('subAttributes')->get()->toArray();
            $responseData['province']          = \App\States::with('cities')->get()->toArray();

            $returnData = UtilityController::Generateresponse(1, 'EMPTY_MESSAGE', Response::HTTP_OK, $responseData, 1);
            return $returnData;
        } catch (\Exception $e) {
            $sendExceptionMail = UtilityController::Sendexceptionmail($e);
            //Code to send mail ends here
            return UtilityController::Setreturnvariables();
        }

    }

    /*
    |--------------------------------------------------------------------------
    | Getmessage
    |--------------------------------------------------------------------------
    |
    | Author    : Jainam Shah
    | Purpose   : get message from constant file
    | In Params : 
    | Date      : 25 December 2018
    |
     */
    public static function Getmessage($message)
    {
        return Config('constants.messages.' . $message);
    }

}
