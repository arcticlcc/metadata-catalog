<?php

namespace MetaCat\Entity;

class Project
{
    protected $projectid;
    protected $json;
    protected $html;
    protected $xml;

    private $products;
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->products = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set projectid.
     *
     * @param string $id
     *
     * @return Project
     */
    public function setProjectid($id)
    {
        $this->projectid = $id;

        return $this;
    }

    /**
     * Get projectid.
     *
     * @return guid
     */
    public function getProjectid()
    {
        return $this->projectid;
    }

    /**
     * Set json.
     *
     * @param jsonb $json
     *
     * @return Project
     */
    public function setJson($json)
    {
        $this->json = $json;

        return $this;
    }

    /**
     * Get json.
     *
     * @return jsonb
     */
    public function getJson()
    {
        return $this->json;
    }

    /**
     * Set xml.
     *
     * @param string $xml
     *
     * @return Project
     */
    public function setXml($xml)
    {
        $this->xml = $xml;

        return $this;
    }

    /**
     * Get xml.
     *
     * @return string
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Set html.
     *
     * @param string $html
     *
     * @return Project
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Get html.
     *
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * Add product.
     *
     * @param \MetaCat\Entity\Product $product
     *
     * @return Project
     */
    public function addProduct(\MetaCat\Entity\Product $product)
    {
        $this->products[] = $product;

        return $this;
    }

    /**
     * Remove product.
     *
     * @param \MetaCat\Entity\Product $product
     */
    public function removeProduct(\MetaCat\Entity\Product $product)
    {
        $this->products->removeElement($product);
    }

    /**
     * Get products.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProducts()
    {
        return $this->products;
    }
}
