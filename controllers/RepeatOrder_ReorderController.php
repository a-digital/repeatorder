<?php
/**
 * Repeat Order plugin for Craft CMS
 *
 * RepeatOrder_Reorder Controller
 *
 * --snip--
 * Generally speaking, controllers are the middlemen between the front end of the CP/website and your plugin’s
 * services. They contain action methods which handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering post data, saving it on a model,
 * passing the model off to a service, and then responding to the request appropriately depending on the service
 * method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what the method does (for example,
 * actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 * --snip--
 *
 * @author    Matt Shearing
 * @copyright Copyright (c) 2017 Matt Shearing
 * @link      https://adigital.agency
 * @package   RepeatOrder
 * @since     1.0.0
 */

namespace Craft;

class RepeatOrder_ReorderController extends BaseController
{

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = array('actionAddOrder');

    /**
     * Handle a request going to our plugin's index action URL, e.g.: actions/repeatOrder
     */
    public function actionIndex()
    {
	    
    }
    
    public function actionAddOrder()
    {
	    $this->requirePostRequest();
		$items = craft()->repeatOrder->getOrder(craft()->request->getPost('orderId'));
		$message = "";
		$pluginSettings = craft()->plugins->getPlugin('repeatorder')->getSettings();
		$productTypes = $pluginSettings->productTypes;
		foreach ($items as $key => $item) {
		    if (in_array($item["typeId"], $productTypes)) {
			    $snapshot = json_decode($item["snapshot"]);
			    $message .= "<p>".$snapshot->description." cannot be reordered due to stock constraints, please <a href='/".$snapshot->product->uri."/".$item["sku"]."'>add the product</a> manually.</p>";
		    } else {
			    $response = craft()->repeatOrder->addToOrder($item);
			    if (isset($response["error"])) {
				    if (isset($response["error"]["lineItems"])) {
					    $message .= "<p>".$response["error"]["lineItems"].".</p>";
				    }
			    }
		    }
	    }
	    craft()->userSession->setNotice($message);
	    $this->redirectToPostedUrl();
    }
}