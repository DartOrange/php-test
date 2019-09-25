<?php

namespace Horoshop\App\Controller;

use Horoshop\Exceptions\UnavailablePageException;

class Pages
{

    public function GetPage(string $filename, int $page = 1, int $perPage = 10, string $currency = "UAH") 
    {
    	$json = file_get_contents($filename);
    	$file = json_decode($json, true);

        //Get pages and items index
        $pages = round(count($file['products']) / $perPage);
        $firstItemIndex = ($page - 1) * $perPage; 
        $lastItemIndex = $firstItemIndex + $perPage; 

        //Initialize ellements for result
        $result = [];
        $item = [];
        $items = [];
        $price = [];
        $discount = [];

        //Begin Item array create
        if($page <= $pages)
        {
            for ($itemIndex = $firstItemIndex;  $itemIndex < $lastItemIndex; $itemIndex++) { 
            
            //Item
            $item_ID = self::GetDataFromArrayThird($file, 'products', $itemIndex, 'id');
            $item_title = self::GetDataFromArrayThird($file, 'products', $itemIndex, 'title');
            
            //Cat
            $cat_ID_value = self::GetDataFromArrayThird($file, 'products', $itemIndex, 'category');
            
            $cat_ID = self::GetArraySearch($file, $cat_ID_value, 'categories', 'id');
            $cat_title = self::GetDataFromArrayThird($file, 'categories', $cat_ID, 'title');

            //Price

            $item_amount = self::GetDataFromArrayThird($file,'products',$itemIndex,'amount');

            //Discount
            $discount_cat_ID = self::GetArraySearch($file, $cat_ID_value, 'discounts', 'related_id');
            $discount_prod_ID = self::GetArraySearch($file, $item_ID, 'discounts', 'related_id');

            $discount = self::GetDiscount($file, $discount_cat_ID, $discount_prod_ID, $item_amount);

            //Convert with currency
            $price = self::GetWithCurrency($file, $currency, $item_amount, $discount['discount_price']);


            //Push item
            $item = [
                        'id' => $item_ID,
                        'title' => $item_title,
                        'category' => [
                                        'id' => $cat_ID_value,
                                        'title' => $cat_title,
                                        ],
                        'price' => [
                                    'amount' => (float)$price['item_amount'],
                                    'discounted_price' => (float)$price['item_discount_price'],
                                    'currency' => $currency,
                                    'discount' => $discount['item_discount'],
                                    ],
                        ];
            
            //Push item to array items
            array_push($items, $item);
            }
            
            //Create result
            $result = [     'items' => $items,
                            'perPage' => $perPage,
                            'pages' => $pages,
                            'page' => $page,
                            ];

            return json_encode($result, true); 

        }

        throw new UnavailablePageException();
        
    }




    //Get discount price 

    function GetDiscount($array, $cat_ID, $prod_ID, $amount)
    {
        

        $item_discount_cat_price = (gettype($cat_ID) == "integer")?self::DiscountPrice($amount,
                                                    self::GetDataFromArrayThird($array, 'discounts', $cat_ID,'type'),
                                                    self::GetDataFromArrayThird($array, 'discounts', $cat_ID,'value')
                                                    ):0;
        $item_discount_prod_price = (gettype($prod_ID) == "integer")?self::DiscountPrice($amount,
                                                    self::GetDataFromArrayThird($array, 'discounts', $prod_ID,'type'),
                                                    self::GetDataFromArrayThird($array, 'discounts', $prod_ID,'value')
                                                    ):0;
        $cat_discount = (($amount - $item_discount_cat_price) == $amount)?0:$amount - $item_discount_cat_price;
        $prod_discount = (($amount - $item_discount_prod_price) == $amount)?0:$amount - $item_discount_prod_price;

        if($cat_discount > $prod_discount){
            $item_discount_price = $item_discount_cat_price;
            $itemDiscount = self::GetItemDiscount($array, $cat_ID);
        }elseif($cat_discount < $prod_discount){
            $item_discount_price = $item_discount_prod_price;
            $itemDiscount = self::GetItemDiscount($array, $prod_ID);
        }else{
            $item_discount_price = $amount;
            $itemDiscount = [];
        }
        return [
                    'discount_price' => $item_discount_price,
                    'item_discount' => $itemDiscount,
                    ];
    }



    function GetItemDiscount($array, $id)
    {
        return [
                    'type' => self::GetDataFromArrayThird($array,'discounts',$id,'type'),
                    'value' => self::GetDataFromArrayThird($array,'discounts',$id,'value'),
                    'relation' => self::GetDataFromArrayThird($array,'discounts',$id,'relation'),
                    ];
    }


    function DiscountPrice($amount,$type,$value) : float
    {
        if ($type == "absolute") {
            return self::RoundUp(floatval($amount) - floatval($value),2); 
        }
        elseif($type == "percent"){
            return self::RoundUp(floatval($amount) - floatval($amount)*floatval($value/100),2);
        }
        else{
            return 0;
        }
    }

     //Get price with currency
    public function GetWithCurrency($array, $currency, $amount, $discount)
    {
        $course_value = floatval(1);
        if($currency != "UAH"){
            $currency_ID = self::GetArraySearch($array, 'UAH', 'currencies', 'title');
            $course_value = self::GetDataFromArrayFourth($array, 'currencies', $currency_ID, 'rates', $currency);
        }

        return [
                    'item_amount' => self::RoundUp($amount * $course_value,2),
                    'item_discount_price' => self::RoundUp($discount * $course_value,2),
                    ]; 
    }

    //First item index
    public function GetFirstItemIndex($page, $per_page) : int
    {
        return ($page - 1) * $per_page;
    }

    //Last item index
    public function GetLastItemIndex($first_index, $per_page) : int
    {
        return $first_index + $per_page;
    }

    //Work with arrays
    public function GetDataFromArrayThird($array, $first_index, $second_index, $third_index)
    {
        return $array[$first_index][$second_index][$third_index];
    }

    public function GetDataFromArrayFourth($array, $first_index, $second_index, $third_index, $fourth_index)
    {
        return $array[$first_index][$second_index][$third_index][$fourth_index];
    }

    public function GetArraySearch($array, $first_index, $second_index, $third_index)
    {
        // var_dump($first_index, $second_index, $third_index);
        return array_search($first_index, array_column($array[$second_index], $third_index));
    }

    public function RoundUp($value, $places=0) {
      if ($places < 0) { $places = 0; }
      $mult = pow(10, $places);
      return ceil($value * $mult) / $mult;
    }
    
}