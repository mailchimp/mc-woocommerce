<?php

class MailChimp_WooCommerce_SmsProgram {
    public $program_id = null;
    public $registration_status = 'inactive';
    public $program_name = null;
    public $program_sms_phone_number = null;
    public $can_send = false;
    public $double_opt_in = false;

    const STATUS_UNSPECIFIED = 'REGISTRATION_STATUS_UNSPECIFIED';
    const STATUS_DRAFT = 'REGISTRATION_STATUS_DRAFT';
    const STATUS_PROCESSING = 'REGISTRATION_STATUS_PROCESSING';
    const STATUS_BRAND_UNVERIFIED = 'REGISTRATION_STATUS_BRAND_UNVERIFIED';
    const STATUS_PENDING_APPROVAL = 'REGISTRATION_STATUS_PENDING_APPROVAL';
    const STATUS_ACTIVE = 'REGISTRATION_STATUS_ACTIVE';
    const STATUS_REJECTED = 'REGISTRATION_STATUS_REJECTED';
    const STATUS_NOT_STARTED = 'REGISTRATION_STATUS_NOT_STARTED';

    /**
     * @return string
     */
    public function getRegistrationStatusForUI()
    {
         switch (strtoupper($this->registration_status)) {
             case static::STATUS_UNSPECIFIED:
             case static::STATUS_DRAFT:
             case static::STATUS_NOT_STARTED:
             case static::STATUS_REJECTED:
                return 'inactive';
            case static::STATUS_PROCESSING:
            case static::STATUS_PENDING_APPROVAL:
            case static::STATUS_BRAND_UNVERIFIED:
                return 'pending';
            case static::STATUS_ACTIVE:
                return 'active';
             default:
                 return null;
        }
    }

    /**
     * @return bool
     */
    public function isUnspecified()
    {
        return !empty($this->registration_status) && strtoupper($this->registration_status) === static::STATUS_UNSPECIFIED;
    }

    /**
     * @return bool
     */
    public function isUnverified()
    {
        return !empty($this->registration_status) && strtoupper($this->registration_status) === static::STATUS_BRAND_UNVERIFIED;
    }

    /**
     * @return bool
     */
    public function isNotStarted()
    {
        return !empty($this->registration_status) && strtoupper($this->registration_status) === static::STATUS_NOT_STARTED;
    }

    /**
     * @return bool
     */
    public function isDraft()
    {
        return !empty($this->registration_status) && strtoupper($this->registration_status) === static::STATUS_DRAFT;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return !empty($this->registration_status) && strtoupper($this->registration_status) === static::STATUS_PENDING_APPROVAL;
    }

    /**
     * @return bool
     */
    public function isProcessing()
    {
        return !empty($this->registration_status) && strtoupper($this->registration_status) === static::STATUS_PROCESSING;
    }

    /**
     * @return bool
     */
    public function isRejected()
    {
        return !empty($this->registration_status) && ($this->registration_status) === static::STATUS_REJECTED;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return !empty($this->registration_status) && ($this->registration_status) === static::STATUS_ACTIVE;
    }

    public function toArray()
    {
        return [
            'program_id' => $this->program_id,
            'registration_status' => $this->registration_status,
            'program_name' => $this->program_name,
            'program_sms_phone_number' => $this->program_sms_phone_number,
            'can_send' => $this->can_send,
            'double_opt_in' => $this->double_opt_in,
        ];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }
}