<?php

namespace MetaCat\Entity;

class Product {
    protected $productid;
    protected $json;
    protected $html;
    protected $xml;

    private $project;
    private $projectid;

    /**
     * Get productid
     *
     * @return guid
     */
    public function getProductid() {
        return $this->productid;
    }

    /**
     * Set projectid
     *
     * @param guid $projectid
     *
     * @return Product
     */
    public function setProjectid($projectid) {
        $this->projectid = $projectid;

        return $this;
    }

    /**
     * Get projectid
     *
     * @return guid
     */
    public function getProjectid() {
        return $this->projectid;
    }

    /**
     * Set json
     *
     * @param jsonb $json
     *
     * @return Product
     */
    public function setJson($json) {
        $this->json = $json;

        return $this;
    }

    /**
     * Get json
     *
     * @return jsonb
     */
    public function getJson() {
        return $this->json;
    }

    /**
     * Set xml
     *
     * @param string $xml
     *
     * @return Product
     */
    public function setXml($xml) {
        $this->xml = $xml;

        return $this;
    }

    /**
     * Get xml
     *
     * @return string
     */
    public function getXml() {
        return $this->xml;
    }

    /**
     * Set html
     *
     * @param string $html
     *
     * @return Product
     */
    public function setHtml($html) {
        $this->html = $html;

        return $this;
    }

    /**
     * Get html
     *
     * @return string
     */
    public function getHtml() {
        return $this->html;
    }

    /**
     * Set project
     *
     * @param \MetaCat\Entity\Project $project
     *
     * @return Product
     */
    public function setProject(\MetaCat\Entity\Project $project = null) {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project
     *
     * @return \MetaCat\Entity\Project
     */
    public function getProject() {
        return $this->project;
    }

}
