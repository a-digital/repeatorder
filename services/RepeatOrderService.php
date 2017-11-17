<?php
/**
 * Repeat Order plugin for Craft CMS
 *
 * RepeatOrder Service
 *
 * --snip--
 * All of your plugin’s business logic should go in services, including saving data, retrieving data, etc. They
 * provide APIs that your controllers, template variables, and other plugins can interact with.
 *
 * https://craftcms.com/docs/plugins/services
 * --snip--
 *
 * @author    Matt Shearing
 * @copyright Copyright (c) 2017 Matt Shearing
 * @link      https://adigital.agency
 * @package   RepeatOrder
 * @since     1.0.0
 */

namespace Craft;

class RepeatOrderService extends BaseApplicationComponent
{
    /**
     * This function can literally be anything you want, and you can have as many service functions as you want
     *
     * From any other plugin file, call it like this:
     *
     *     craft()->repeatOrder->exampleService()
     */
    public function exampleService()
    {
    }
    
    public function getOrder($orderId)
    {
	    $query = craft()->db->createCommand();
	    $orderItems = $query->select('*')
					->from('commerce_lineitems')
					->join('commerce_variants', 'craft_commerce_lineitems.purchasableId=craft_commerce_variants.id')
					->join('commerce_products', 'craft_commerce_variants.productId=craft_commerce_products.id')
					->join('content', 'craft_content.elementId=craft_commerce_products.id')
					->where('orderId="'.$orderId.'"')
					->queryAll();
		return $orderItems;
    }
    
    public function addToOrder($item)
    {
	    $cart = craft()->commerce_cart->getCart();
        $cartSaved = false;
        $updateErrors = [];
        $message = "";

        if (!is_null($item['purchasableId']))
        {
            $purchasableId = $item['purchasableId'];
            $note = $item['note'];
            $options = json_decode($item['options'], true);
            
            $totalQty = $item['qty'];
            foreach($cart->lineItems as $cartKey => $lineItem) {
	            if ($lineItem['purchasableId'] == $purchasableId) {
		            $totalQty = $item['qty'] + $lineItem['qty'];
	            }
            }
            
            if ($totalQty > $item['stock'] && $item['unlimitedStock'] != 1) {
	            $message = $item['title']." quantity was reduced due to current stock levels";
	            $addToCartError = Craft::t('{error}', [
                    'error' => $message,
                ]);
                $updateErrors['lineItems'] = $addToCartError;
                if ($totalQty !== $item['qty']) {
	                if ($totalQty - $item['qty'] >= $item['stock']) {
		                $qty = 0;
	                } else {
			            $qty = $totalQty - $item['stock'];
		            }
	            } else {
		            $qty = $item['stock'];
	            }
            } else {
	            $qty = $item['qty'];
            }
            $error = '';
            
            if ($qty == 0) {
	            $message = $item['title']." was removed as it is currently out of stock";
	            $addToCartError = Craft::t('{error}', [
                    'error' => $message,
                ]);
                $updateErrors['lineItems'] = $addToCartError;
            }
            
            if (!craft()->commerce_cart->addToCart($cart, $purchasableId, $qty, $note, $options, $error))
            {
                $addToCartError = Craft::t('{error}', [
                    'error' => $error,
                ]);
                $updateErrors['lineItems'] = $addToCartError;
            }
            else
            {
                $cartSaved = true;
            }
        }

        // Clean up error array
        $updateErrors = array_filter($updateErrors);

        if (empty($updateErrors))
        {
		    return array('success' => true);
        }
        else
        {
            return array('error' => $updateErrors);
        }
    }

}