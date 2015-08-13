<?php
namespace Dsc\Admin\Models;

use Dsc\Lib\Validator\Mongo\Uniqueness;

/**
 * Dsc\Admin\Models\Users
 * All the users registered in the application
 */
class Users extends \Dsc\Lib\Collection
{

    /**
     *
     * @var integer
     */
    public $_id;

    /**
     *
     * @var string
     */
    public $username;
    
    /**
     *
     * @var string
     */
    public $first_name;
    
    /**
     *
     * @var string
     */
    public $last_name;

    /**
     *
     * @var string
     */
    public $email;

    /**
     *
     * @var string
     */
    public $password;

    /**
     *
     * @var string
     */
    public $mustChangePassword;

    /**
     *
     * @var string
     */
    public $profilesId;

    /**
     *
     * @var string
     */
    public $banned;

    /**
     *
     * @var string
     */
    public $suspended;

    /**
     *
     * @var string
     */
    public $active;

    /**
     * Before create the user assign a password
     */
    public function beforeValidationOnCreate()
    {
        if (empty($this->password)) {

            // Generate a plain temporary password
            $tempPassword = preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(openssl_random_pseudo_bytes(12)));

            // The user must change its password in first login
            $this->mustChangePassword = 'Y';

            // Use this password as default
            $this->password = $this->getDI()
                ->getSecurity()
                ->hash($tempPassword);
        } else {
            // The user must not change its password in first login
            $this->mustChangePassword = 'N';
        }

        // The account must be confirmed via e-mail
        $this->active = 'N';

        // The account is not suspended by default
        $this->suspended = 'N';

        // The account is not banned by default
        $this->banned = 'N';
    }

    /**
     * Send a confirmation e-mail to the user if the account is not active
     */
    public function afterSave()
    {
        if ($this->active == 'N' && $this->email) {

            $emailConfirmation = new EmailConfirmations();

            $emailConfirmation->usersId = $this->id;

            if ($emailConfirmation->save()) {
                $this->getDI()
                    ->getFlash()
                    ->notice('A confirmation mail has been sent to ' . $this->email);
            }
        }
    }

    /**
     * Validate that emails are unique across users
     */
    public function validation()
    {
        $uniqueness = new Uniqueness(
                array(
                         'collection' => $this->getSource(),
                         'field' => 'email', // accepts dot.notation
                         'message' => 'This email is already registered',
                     ),
                $this->getDI()->get('mongo')
        );
                
        $this->validate($uniqueness);

        return $this->validationHasFailed() != true;
    }

    public function initialize()
    {
        /*
        $this->belongsTo('profilesId', 'Dsc\Admin\Models\Profiles', 'id', array(
            'alias' => 'profile',
            'reusable' => true
        ));

        $this->hasMany('id', 'Dsc\Admin\Models\SuccessLogins', 'usersId', array(
            'alias' => 'successLogins',
            'foreignKey' => array(
                'message' => 'User cannot be deleted because he/she has activity in the system'
            )
        ));

        $this->hasMany('id', 'Dsc\Admin\Models\PasswordChanges', 'usersId', array(
            'alias' => 'passwordChanges',
            'foreignKey' => array(
                'message' => 'User cannot be deleted because he/she has activity in the system'
            )
        ));

        $this->hasMany('id', 'Dsc\Admin\Models\ResetPasswords', 'usersId', array(
            'alias' => 'resetPasswords',
            'foreignKey' => array(
                'message' => 'User cannot be deleted because he/she has activity in the system'
            )
        ));
        */
    }
    
    protected function fetchConditions()
    {
        parent::fetchConditions();
        
        $filter_username = $this->getState('filter.username');
        if (strlen($filter_username)) {
            $this->setCondition( 'username', $filter_username );
        }        
    }
}