<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 6/13/17
 * Time: 1:19 PM
 */
class MailChimp_WooCommerce_PromoRule
{
    /**
     * @var string
     * @title Promo Rule Foreign ID
     * @default = null
     * @description
     * A unique identifier for the promo rule. If Ecomm platform does not support promo rule,
     * use promo code id as promo rule id.
     * Restricted to UTF-8 characters with max length 50
     */
    protected $id;

    /**
     * @var string
     * @title Title
     * @default null
     * @description The title that will show up in promotion campaign. Restricted to UTF-8 characters with max length 100
     */
    protected $title;

    /**
     * @var string
     * @title Description
     * @default null
     * @description The description of a promotion
     */
    protected $description;

    /**
     * @var \DateTime
     * @title Start Time
     * @default null
     * @description The date and time when the promotion starts in ISO 8601 format
     */
    protected $starts_at;

    /**
     * @var \DateTime
     * @title Start Time
     * @default null
     * @description The date and time when the promotion starts in ISO 8601 format
     */
    protected $ends_at;

    /**
     * @var float
     * @title Amount
     * @required
     * @description The amount of discount; Positive dollar or percentage amount.
     */
    protected $amount;

    /**
     * @var string
     * @title Type
     * @required
     * @description One of ‘fixed’ , ‘percentage’
     */
    protected $type;

    /**
     * @var string
     * @title Target
     * @required
     * @description One of ‘per_item’, ‘total’, ‘shipping’
     */
    protected $target;

    /**
     * @var boolean
     * @title Enabled
     * @default true
     * @description Whether the promo rule is currently enabled
     */
    protected $enabled = true;

    /**
     * @var \DateTime
     * @title Start Time
     * @default null
     * @description The date and time when the promotion starts in ISO 8601 format
     */
    protected $created_at_foreign;

    /**
     * @var \DateTime
     * @title Start Time
     * @default null
     * @description The date and time when the promotion starts in ISO 8601 format
     */
    protected $updated_at_foreign;

    /**
     * @return array
     */
    public function getValidation()
    {
        return [
            'id' => 'required',
            'amount' => 'required|number',
            'type' => 'required',
            'target' => 'required',
            'enabled' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'created_at_foreign' => 'date',
            'updated_at_foreign' => 'date',
        ];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param null $id
     * @return MailChimp_WooCommerce_PromoRule
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $name
     * @return MailChimp_WooCommerce_PromoRule
     */
    public function setTitle($name)
    {
        $this->title = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return MailChimp_WooCommerce_PromoRule
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param \DateTime $date
     * @return $this
     */
    public function setStartsAt(\DateTime $date)
    {
        $this->starts_at = (string) $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartsAt()
    {
        return $this->starts_at;
    }

    /**
     * @param \DateTime $date
     * @return $this
     */
    public function setEndsAt(\DateTime $date)
    {
        $this->ends_at = (string) $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndsAt()
    {
        return $this->ends_at;
    }

    /**
     * @param $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->enabled;
    }

    /**
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return MailChimp_WooCommerce_PromoRule
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return $this
     */
    public function setTypeFixed()
    {
        $this->type = 'fixed';

        return $this;
    }

    /**
     * @return $this
     */
    public function setTypePercentage()
    {
        $this->type = 'percentage';

        return $this;
    }

    /**
     * @return $this
     */
    public function setTargetTypePerItem()
    {
        $this->target = 'per_item';

        return $this;
    }

    /**
     * @return $this
     */
    public function setTargetTypeShipping()
    {
        $this->target = 'shipping';

        return $this;
    }

    /**
     * @return $this
     */
    public function setTargetTypeTotal()
    {
        $this->target = 'total';

        return $this;
    }
    /**
     * @param \DateTime $time
     * @return $this
     */
    public function setUpdatedAt(\DateTime $time)
    {
        $this->updated_at_foreign = (string) $time;

        return $this;
    }

    /**
     * @return null
     */
    public function getUpdatedAt()
    {
        return $this->updated_at_foreign;
    }

    /**
     * @param \DateTime $time
     * @return $this
     */
    public function setCreatedAt(\DateTime $time)
    {
        $this->created_at_foreign = (string) $time;

        return $this;
    }

    /**
     * @return null
     */
    public function getCreatedAt()
    {
        return $this->created_at_foreign;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return mailchimp_array_remove_empty([
            'id' => (string) $this->getId(),
            'title' => (string) $this->getTitle(),
            'description' => (string) $this->getDescription(),
            'starts_at' => (string) $this->getStartsAt(),
            'ends_at' => (string) $this->getEndsAt(),
            'amount' => floatval($this->getAmount()),
            'type' => (string) $this->getType(),
            'target' => (string) $this->getTarget(),
            'enabled' => (bool) $this->isEnabled(),
            'created_at_foreign' => (string) $this->getCreatedAt(),
            'updated_at_foreign' => (string) $this->getUpdatedAt(),
        ]);
    }

    /**
     * @param array $data
     * @return MailChimp_WooCommerce_PromoRule
     */
    public function fromArray(array $data)
    {
        $singles = [
            'id',
            'title',
            'description',
            'starts_at',
            'ends_at',
            'amount',
            'type',
            'target',
            'enabled',
            'created_at_foreign',
            'updated_at_foreign'
        ];

        foreach ($singles as $key) {
            if (array_key_exists($key, $data)) {
                $this->$key = $data[$key];
            }
        }

        return $this;
    }
}
