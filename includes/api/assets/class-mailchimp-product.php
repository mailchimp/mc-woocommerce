<?php

/**
 * Created by Vextras.
 *
 * Name: Ryan Hungate
 * Email: ryan@vextras.com
 * Date: 3/8/16
 * Time: 2:17 PM
 */
class MailChimp_WooCommerce_Product
{
    protected $id;
    protected $title;
    protected $handle = null;
    protected $url = null;
    protected $description = null;
    protected $type = null;
    protected $vendor = null;
    protected $image_url = null;
    protected $variants = array();
    protected $published_at_foreign = null;

    /**
     * @return array
     */
    public function getValidation()
    {
        return array(
            'id' => 'required|string',
            'title' => 'required|string',
            'handle' => 'string',
            'url' => 'url',
            'description' => 'string',
            'type' => 'string',
            'vendor' => 'string',
            'image_url' => 'url',
            'variants' => 'required|array',
            'published_at_foreign' => 'date',
        );
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return MailChimp_WooCommerce_Product
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return MailChimp_WooCommerce_Product
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return null
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @param null $handle
     * @return MailChimp_WooCommerce_Product
     */
    public function setHandle($handle)
    {
        $this->handle = $handle;

        return $this;
    }

    /**
     * @return null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param null $url
     * @return MailChimp_WooCommerce_Product
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param null $description
     * @return MailChimp_WooCommerce_Product
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param null $type
     * @return MailChimp_WooCommerce_Product
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @param null $vendor
     * @return MailChimp_WooCommerce_Product
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * @return null
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }

    /**
     * @param null $image_url
     * @return MailChimp_WooCommerce_Product
     */
    public function setImageUrl($image_url)
    {
        $this->image_url = $image_url;

        return $this;
    }

    /**
     * @return array
     */
    public function getVariations()
    {
        return $this->variants;
    }

    /**
     * @param MailChimp_WooCommerce_ProductVariation $variation
     * @return MailChimp_WooCommerce_Product
     */
    public function addVariant(MailChimp_WooCommerce_ProductVariation $variation)
    {
        $this->variants[] = $variation;

        return $this;
    }

    /**
     * @return string
     */
    public function getPublishedAtForeign()
    {
        return $this->published_at_foreign;
    }

    /**
     * @param \DateTime $time
     * @return MailChimp_WooCommerce_Product
     */
    public function setPublishedAtForeign(\DateTime $time)
    {
        $this->published_at_foreign = $time->format('Y-m-d H:i:s');

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return mailchimp_array_remove_empty(array(
            'id' => (string) $this->getId(),
            'title' => $this->getTitle(),
            'handle' => (string) $this->getHandle(),
            'url' => (string) $this->getUrl(),
            'description' => (string) $this->getDescription(),
            'type' => (string) $this->getType(),
            'vendor' => (string) $this->getVendor(),
            'image_url' => (string) $this->getImageUrl(),
            'variants' => array_map(function ($item) {
                return $item->toArray();
            }, $this->getVariations()),
            'published_at_foreign' => (string) $this->getPublishedAtForeign(),
        ));
    }

    /**
     * @param array $data
     * @return MailChimp_WooCommerce_Product
     */
    public function fromArray(array $data)
    {
        $singles = array(
            'id', 'title', 'handle', 'url',
            'description', 'type', 'vendor', 'image_url',
            'published_at_foreign',
        );

        foreach ($singles as $key) {
            if (array_key_exists($key, $data)) {
                $this->$key = $data[$key];
            }
        }

        if (array_key_exists('variants', $data) && is_array($data['variants'])) {
            $this->variants = array();
            foreach ($data['variants'] as $variant) {
                $variation = new MailChimp_WooCommerce_ProductVariation();
                $this->variants[] = $variation->fromArray($variant);
            }
        }

        return $this;
    }
}
