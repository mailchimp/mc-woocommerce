<?php

class Mailchimp_WooCommerce_Product_Category
{
    protected $id;
    protected $name;
    protected $readable_url         = null;
    protected $description          = null;
    protected $type                 = 'category';
    protected $parent_collection_id = null;
    protected $image_url            = null;
    protected $updated_at_foreign   = null;

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function setId( $id ) {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function setName( $name ) {
        $this->name = strip_tags( $name );

        return $this;
    }

    /**
     * @return null
     */
    public function getUrl() {
        return $this->readable_url;
    }

    /**
     * @param null $url
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function setUrl( $url ) {
        $this->readable_url = $url;

        return $this;
    }

    /**
     * @return null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * @param null $description
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function setDescription( $description ) {
        $this->description = $description;

        return $this;
    }

    /**
     * @return null
     */
    public function getParentCollectionId() {
        return $this->parent_collection_id;
    }

    /**
     * @param null $parent_category_id
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function setParentCollectionId( $parent_category_id ) {
        $this->parent_collection_id = $parent_category_id;

        return $this;
    }

    /**
     * @return null
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param null $type
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function setType( $type ) {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null
     */
    public function getImageUrl() {
        return $this->image_url;
    }

    /**
     * @param null $image_url
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function setImageUrl( $image_url ) {
        $this->image_url = $image_url;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedAtForeign() {
        return $this->updated_at_foreign;
    }

    /**
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function setUpdatedAtForeign() {
        $time = new DateTime();
        $this->updated_at_foreign = $time->format( 'Y-m-d H:i:s' );

        return $this;
    }

    /**
     * @return array
     */
    public function toArray() {
        return mailchimp_array_remove_empty(
            array(
                'id'                    => (string) $this->getId(),
                'name'                  => $this->getName(),
                'image_url'             => $this->getImageUrl(),
                'readable_url'          => (string) $this->getUrl(),
                'type'                  => (string) $this->getType(),
                'description'           => $this->getDescription(),
                'parent_collection_id'  => (string) $this->getParentCollectionId(),
                'updated_at_foreign'    => (string) $this->getUpdatedAtForeign(),
            )
        );
    }

    /**
     * @param array $data
     * @return Mailchimp_WooCommerce_Product_Category
     */
    public function fromArray( array $data ) {
        $singles = array(
            'id',
            'name',
            'image_url',
            'readable_url',
            'type',
            'description',
            'parent_collection_id',
            'updated_at_foreign',
        );

        foreach ( $singles as $key ) {
            if ( array_key_exists( $key, $data ) ) {
                $this->$key = $data[ $key ];
            }
        }

        return $this;
    }
}