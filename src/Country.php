<?php

namespace App;

class Country
{
    public $id;
    public $name;
    public $capital;
    public $region;
    public $population;
    public $currency_code;
    public $exchange_rate;
    public $estimated_gdp;
    public $flag_url;
    public $last_refreshed_at;

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        if (isset($data['estimated_gdp']) && is_null($data['estimated_gdp'])) {
            $this->estimated_gdp = null;
        }
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capital' => $this->capital,
            'region' => $this->region,
            'population' => $this->population,
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'estimated_gdp' => $this->estimated_gdp,
            'flag_url' => $this->flag_url,
            'last_refreshed_at' => $this->last_refreshed_at,
        ];
    }
}
