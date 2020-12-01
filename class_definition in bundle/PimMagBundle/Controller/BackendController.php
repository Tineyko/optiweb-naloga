<?php
namespace PimMagBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Pimcore\Model\DataObject;
use Doctrine\Common\Reflection\Compatibility\ReflectionMethod;
use Pimcore\Model\DataObject\Data;
use Pimcore\Model\DataObject\Data\ImageGallery;
use Pimcore\Model\Asset;
use Pimcore\Model\Assets\Image;
use Pimcore\Model\Asset\Image\Thumbnail;

class BackendController
{
    /*
     * @param Request $request
     * @Route("/admin/backend-demo")
     */
    public function testingAction(Request $request) {

        if($id = (int)$request->get('product_id')) {
            if($object = DataObject\ShopProduct::getById($id)) {

              $data = [];
              /* GET ALL VALUES FROM OBJECT */
                $objectReflection = new \ReflectionObject($object);
                foreach($object->getClass()->getFieldDefinitions() as $fieldDefinition) {
                    $fieldName = $fieldDefinition->getName();
                    $getterMethod =  $objectReflection->getMethod("get" . ucfirst($fieldName));
                    $fieldValue = $getterMethod->invoke($object);
                    $fieldType = $fieldDefinition->getFieldtype();
                    $data[$fieldName] = $fieldValue;
                    if($fieldName == 'image_gallery') {
                      $images_object =  $object->getImage_gallery();
                      foreach ($images_object->getItems() as $galleryItem) {
                          $image = $galleryItem->getImage();
                          $data['image_ids'][] = $image->getId();
                          $asset = Asset::getById($image->getId());
                          $thumb = $asset->getThumbnail();
                          $path = $thumb->getPath();
                          $data['thumb'][] = $path;
                      }
                    }
                }
                if(!isset($data['product_visibility'])){
                  $data['product_visibility'] = 4;/*4->'catalog',*/
                }
                /*CREATE PRODCT OBJECT */
                  $productData = array(
                    'sku'               => $data['product_sku'],
                    'name'              => $data['product_name'],
                    'visibility'        => $data['product_visibility'],
                    'type_id'           => 'simple',
                    'price'             => $data['product_price'],
                    'attribute_set_id'  => 4,
                    'status'            => $data['product_status'],
                    'extension_attributes' => array(
                      'stock_item' => array(
                        'qty' => $data['product_quantity'],
                        'is_in_stock' => $data['product_stock_status']
                      )
                    ),
                    'custom_attributes' => array(
                      array( 'attribute_code' => 'description', 'value' => $data['product_description']),
                      array( 'attribute_code' => 'short_description', 'value' => $data['product_short_description']),
                    )
                  );
                  $productData = json_encode(array('product' => $productData));

                  /*API INFO*/
                  $api_credentials = array(
                    'path' => 'http://127.0.0.1/magento_test/index.php',
                    'apiKey' => '9v1n6wgotejy6gvwvd55qunutm26r85i'
                  );

                  /* API CALLS */
                  $request_url = '127.0.0.1/magento_test/index.php/rest/V1/products/';
                  $curl_header = array("Content-Type: application/json", "Authorization: Bearer " . $api_credentials['apiKey']);
                  //check if product/sku exists
                  $ch = curl_init($request_url.$data['product_sku']);
                  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                  curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_header);

                  $result = curl_exec($ch);
                  $decoded_result = json_decode($result);

                  //$data['does_exist_response'] = json_decode($result);

                  if(isset($decoded_result->id)) {
                    //exists, update
                    $ch = curl_init($request_url.$data['product_sku']);
                    curl_setopt($ch,CURLOPT_POSTFIELDS, $productData);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_header);

                    $res = curl_exec($ch);
                    $update_result = json_decode($res);
                    $timestamp = $update_result->updated_at;
                    $entity_id = $update_result->id;
                    //$data['update_response'] = $update_result;
                  } else {
                    //create
                    $ch = curl_init($request_url);
                    curl_setopt($ch,CURLOPT_POSTFIELDS, $productData);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_header);

                    $res = curl_exec($ch);
                    $create_result = json_decode($res);
                    $timestamp = $create_result->updated_at;
                    $entity_id = $create_result->id;
                    //$data['create_response'] = $create_result;
                  }


                  //$data['timestamp'] = $timestamp;
                  //$data['entity_id'] = $entity_id;

                  /* SAVE VALUES IN OBJECT */
                  $setFieldMethod = $objectReflection->getMethod('set' . ucfirst('timestamp'));
                  $setFieldMethod->invoke($object, $timestamp);
                  $setFieldMethod = $objectReflection->getMethod('set' . ucfirst('entity_id'));
                  $setFieldMethod->invoke($object, $entity_id);
                  $object->save();

                return new JsonResponse($data);
            }
        }

        return new JsonResponse(0);
    }
}
