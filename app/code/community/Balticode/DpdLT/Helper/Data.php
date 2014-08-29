<?php

/*

 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * or OpenGPL v3 license (GNU Public License V3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * or
 * http://www.gnu.org/licenses/gpl-3.0.txt
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@balticode.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category   Balticode
 * @package    Balticode_Dpd
 * @copyright  Copyright (c) 2013 Aktsiamaailm LLC (http://en.balticode.com/)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt  GNU Public License V3.0
 * @author     Matis Halmann
 * 
 
 */

/**
 * <p>Helper class for DPD shipping method related actions</p>
 *
 * @author Matis Halmann
 */
class Balticode_DpdLT_Helper_Data extends Mage_Core_Helper_Abstract {
    protected $_apis = array();

    /**
     * <p>Gets cached DPD API instance for specified Magento store id and shipping method code.</p>
     * @param string $storeId store id to fetch the api for
     * @param string $code shipping method code to fetch the api for
     * @return Balticode_DpdLT_Model_Api
     */
    public function getApi($storeId = null, $code = 'balticodedpdlt') {
        Mage::log("getApi data.php storeId:".print_r($storeId, true), null, 'dpdlog.log');
        if ($storeId === null) {
            $storeId = Mage::app()->getStore($storeId)->getId();
        }
        if (isset($this->_apis[$code]) && isset($this->_apis[$code][$storeId])) {
            return $this->_apis[$code][$storeId];
        }
        if (!isset($this->_apis[$code])) {
            $this->_apis[$code] = array();
        }
        $api = Mage::getModel('balticode_dpdlt/api');
        $api->setStore($storeId);
        $api->setCode($code);
        $this->_apis[$code][$storeId] = $api;
        return $this->_apis[$code][$storeId];
    }
    
    
    /**
     * Returns true if shipping method used in the order belongs to DPD carrier
     * @param Mage_Sales_Model_Order $order
     */
    public function isShippingMethodApplicable(Mage_Sales_Model_Order $order) {
        return strpos($order->getShippingMethod(), 'balticodedpdlt') === 0;
    }
    
    public function getBarcodePdf2(array $incrementIds) {
        Mage::log("getBarcodePdf2 incrementIds:".print_r($incrementIds, true), null, 'dpdlog.log');
        foreach ($incrementIds as $incrementId) {
            $barcode[] = Mage::helper('balticode_postoffice')->getBarcode($incrementId);
        }
        
        $requestResult = $this->getApi()->getLabelData($barcode);
        return $requestResult; 
    }

    public function getManifestPdf(array $orderIds) {
        if (!empty($orderIds)) {
                
            $table ='';

            $basename = str_replace('\\','/',Mage::getBaseDir() . '/media/dpd/');
            $today = date('Y m d');
            $logo = '<img style="float:right;" src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'/frontend/default/default/images/dpd/logo.jpg" height="49" width="98">';
            $ISSN = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'/frontend/default/default/images/dpd/issn.jpg" height="17" width="17">';
            //$footer = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'/frontend/default/default/images/dpd/footer.jpg" width="100%">';
            $_code = Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_FLAT;
            $_parent_code = Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_PARCEL_TERMINAL;
            $userId =Mage::getStoreConfig('carriers/'.$_parent_code.'/sendpackage_userid');
            $mfile =  Mage::getBaseDir('media').'/manifest.nr';
            $handle = fopen($mfile, 'r');
            $mNumber = fread($handle,filesize($mfile));
            $mNumber = ($mNumber ? $mNumber = sprintf("%08d", ++$mNumber) : sprintf("%08d", 00000001));
            fclose($handle);

            $handle = fopen($mfile, 'w') or die('Cannot open file:  '.$mfile);
            fwrite($handle, $mNumber);
            fclose($handle);
            $table .= <<<EOT
            <table style="width:2000mm; " border="0" cellspacing="5">
              <tr>
                <td colspan="2">DPD LATVIJA</td>
                <td>Tel:</td>
                <td style="margin-right:100px">67 385 240</td>
                <td colspan="3" rowspan="3">{$logo}</td>
              </tr>
              <tr>
                <td colspan="2">LV 40003393255</td>
                <td>Fakss:</td>
                <td>67 387 288</td>
              </tr>
              <tr>
                <td colspan="2">URIEKSTES 8A</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td><h3>Manifesta nr.</h3></td>
                <td>{$mNumber}</td>
                <td style="width:30mm">Klients:</td>
                <td style="width:40mm">DPD LATVIJA SIA</td>
                <td style="width:30mm">PVN reģ. nr.</td>
                <td>Tel.</td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>Datums:</td>
                <td>{$today}</td>
                <td>{$userId}</td>
                <td>URIEKSTES 8A</td>
                <td>LV 40003393255</td>
                <td>67 385 240</td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>RIGA</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>LV-1005</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
            </table>
            <table style="width:2000mm; " border="0" cellspacing="5">
              <tr border="1px">
                <td>Nr.</td>
                <td>Sūtījuma veids</td>
                <td style="width:40mm">Saņēmējs</td>
                <td style="width:30mm">Tel.</td>
                <td>Svars</td>
                <td style="width:40mm">Sūtījuma Nr.</td>
                <td>ISSN</td>
              </tr>
EOT;
            $i=0;
            $packages=0;
            $weight=0;
            error_reporting(E_ALL);
ini_set('display_errors', 1);
            foreach ($orderIds as $orderId) {
                $order = Mage::getModel('sales/order')->load($orderId);          
                
                $customer_address = $order->getShippingAddress();
                $order_date = explode(" ", $order->getCreatedAt());
                
                $customer_address_street = null;
                $i111=0;
                foreach ($customer_address->getStreet() AS $cust_street_item)
                {
                    $customer_address_street.= (($i111)?"\",\"":"").$cust_street_item;
                    $i111++;
                }

                $payment_method_code = $order->getPayment()->getMethodInstance()->getCode();
                $shipping_method_code = $order->getShippingMethod();
                $barcode = Mage::helper('balticode_postoffice')->getBarcode($order->getIncrementId());
                if (false === strpos($shipping_method_code, $_code)) {
                    $parcel_type = 'Parcel Shop'; 
                }else{
                    if ($payment_method_code == 'cashondelivery')
                        $parcel_type = 'normal parcel,<br>COD, B2C<br><strong>'.number_format($order->getGrandTotal(), 2).'</strong>';  
                    else
                        $parcel_type = 'normal parcel,<br>B2C';
                    
                }
                    $i++;
                    $table .="<tr>";
                    $table .="<td>".$i."</td>";
                    $table .="<td>".$parcel_type."</td>";
                    $table .="<td><p>".$customer_address->getName()."<br>";
                    $table .=$customer_address_street."<br>";
                    $table .=$customer_address->getPostcode()."<br>";
                    $table .="<strong>".$customer_address->getCity()."</strong> </p></td>";
                    $table .="<td>".$customer_address->getTelephone()."</td>";
                    $table .="<td>".$order->getWeight()."</td>";
                    $table .="<td>".$barcode."</td>";
                    $table .="<td>".$ISSN."</td>";
                    $table .="</tr>";

                    
                    $weight+=$order->getWeight();
                    $packages+=$this->_getNumberOfPackagesForOrder($order);
            }
                $table .='<tr>
                    <td><strong>Kopā:</strong></td>
                    <td colspan="2">&nbsp;</td>
                    <td><strong>'.$weight.'</strong></td>
                    <td colspan="3">&nbsp;</td>
                  </tr>
                  <tr>
                    <td>Sūtījumu skats</td>
                    <td colspan="7">'.$i.'</td>
                  </tr>
                  <tr>
                    <td>Paku skaits:</td>
                    <td colspan="7">'.$packages.'</td>
                  </tr>
                </table>
<table border="2px" cellspacing="0" cellpadding="0" width="747px" style="border:2px solid #000;">
  <tr>
    <td style="border-bottom:2px solid #000;"><table border="0" cellspacing="0" cellpadding="0" width="747px">
      <tr>
        <td colspan="3" style="font-weight:bold; padding-left:8px; padding-top:11px;">Papildus pakalpojumi</td>
        </tr>
      <tr>
        <td height="2px" style="padding-left:14px; text-align:left;"><div style="background-color:#000; color:#000; height:2px; width:200px;" /></td>
        <td></td>
        <td></td>
        </tr>
      <tr style="line-height:29px; vertical-align:middle;">
        <td style="padding-left:22px; width:315px;">
<div style="border:2px solid #000;width:10px; height:10px;display:inline;margin-right:5px;"></div>Kraušana</td>
        <td><div style="border:2px solid #000;width:10px; height:10px;display:inline;margin-right:5px;"></div>Gaidišana</td>
        <td width="250px">

<div style="border:2px solid #000;width:20px; height:15px;display:inline;border-style:dotted;border-right-width:0px; margin-left:10px;"></div>
<div style="border:2px solid #000;width:20px; height:15px;display:inline;border-style:dotted;border-right-width:0px;"></div>
<div style="border:2px solid #000;width:20px; height:15px;display:inline;border-style:dotted;border-right-width:0px; margin-right:5px;"></div>min.</td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td style="border-bottom:2px solid #000;"><table border="0" cellspacing="0" cellpadding="0" width="747px;">
      <tr>
        <td style="font-weight:bold; padding-left:15px; line-height:22px; vertical-align:middle; font-size:11px;">NEPIETIEKAMS IEPAKOJUMS NENODROŠINA SŪTĪJUMA SAGLABĀSANU!</td>
      </tr>
      <tr>
        <td height="2px" style="padding-left:14px; text-align:left;"><div style="background-color:#000; color:#000; height:2px; width:324px;" /></td>
      </tr>
      <tr>
        <td style="line-height:16px; font-size:10px; padding-left:5px;">SIA DPD Latvija nav atbildīgs par neatbilstoši iepakotu sūtījumu.</td>
      </tr>
      <tr>
        <td style="line-height:16px; font-size:10px; padding-left:5px;">Neatbilstošs iepakojums - tāds iepakojums, kas nenodrošina sūtījuma satura saglabāšanu transportēšanas laikā un nenodrošina to, ka ar šo sūtījumu netiks bojati citi sūtījumi.</td>
      </tr>
      <tr>
        <td style="line-height:16px; font-size:10px; padding-left:5px;">(atzīmet sūtījuma nr.ISSN kolonnā vai ierakstīt laukā zemāk)</td>
      </tr>
      <tr>
        <td style="padding:4px 0px 4px 10px; vertical-align:middle;">
<div style="border:2px solid #000;width:720px; height:15px;display:inline;margin-right:5px;"></div></td>
      </tr>
    </table></td>
  </tr>
  <tr>
    <td><table border="0" cellspacing="0" cellpadding="0" width="747px">
      <tr>
        <td style="line-height:16px; font-size:10px; padding-left:5px;">* apstiprinu, ka visa aizpildītā informācija ir pareiza</td>
        <td width="150px;" style="padding-left:5px; font-size:12px;">(Nosūtītāja paraksts)</td>
      </tr>
      <tr>
        <td style="line-height:16px; font-size:10px; padding-left:5px;">* esmu informēts, ka ___(ierakstīt skaitu) sūtījumu, kuru numuri atzīmēti, iepakojums nenodrošina to saglabāšanu transportēšanas laikā.</td>
        <td style="padding-left:10px; text-align:left;"><div style="background-color:#000; color:#000; height:2px; width:131px;" /></td>
      </tr>
      <tr>
        <td style="line-height:16px; font-size:10px; padding-left:5px;">Piekrītu, ka šie sūtījumi tiks transportēti šādā iepakojumā.</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td style="line-height:16px; font-size:10px; padding-left:5px;">Packing on my own responsibility.</td>
        <td>&nbsp;</td>
      </tr>
    </table></td>
  </tr>
</table>';
$plotis=150; //px
$plotis_half=$plotis/2;
$table .='<table border="0" cellspacing="0" cellpadding="0" width="747px;" style="text-align:center;">
  <tr >
    <td width="249px;" style="padding-top:10px;">Nosūtītājs:</td>
    <td width="249px;" style="padding-top:10px;">Kurjers:</td>
    <td width="249px;" style="padding-top:10px;">Datums:</td>
  </tr>
  <tr style="line-height:25px;">
    <td colspan="3">&nbsp;</td>
  </tr>
  <tr style="text-align:center;">
    <td width="249px" style="height:3px;"><div style="background-color:#000; display:inline; color:#000; height:2px; width:'.$plotis.'px;margin-left:50%; left:-'.$plotis_half.'px; position:relative; " /></td>
    <td width="249px" style="height:3px;"><div style="background-color:#000; color:#000; display:inline; height:2px; width:'.$plotis.'px; margin-left:50%; left:-'.$plotis_half.'px; position:relative;" /></td>
    <td width="249px" style="height:3px;"><div style="background-color:#000; color:#000; display:inline; height:2px; width:'.$plotis.'px;margin-left:50%; left:-'.$plotis_half.'px; position:relative; " /></td>
  </tr>
  <tr>
    <td>(Vārds, Paraksts)</td>
    <td>(Vārds, Tūre, Paraksts)</td>
    <td>(Datums, Laiks)</td>
  </tr>
</table>';


            $_today = date('Y-m-d H:i:s');
            $date = new Zend_Date($_today);
            $cfooter='<page_footer style="width: 100%;">
                    <table class="page_footer" style="width: 100%;">
                    <tr style="border-top:1px solid #000000">
                        <td style="width: 50%; text-align: left">
                            '.$_today.'
                        </td>
                        <td style="width: 50%; text-align: right">
                            [[page_cu]] / [[page_nb]]
                        </td>
                    </tr>
                </table>
                </page_footer>';
                $requestResult = $this->getApi()->datasend();
            require_once(str_replace('\\','/',Mage::getBaseDir().'/lib/html2fpdf/html2pdf.class.php'));
                $name = 'dpdManifest' . '-'.$date. '.pdf';

                $pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(10, 5, 5, 10));
                $table = '<page style="font-family: freeserif">'.$cfooter.$table.$footer.'</page>';
                $pdf->pdf->SetDisplayMode('real');
                $pdf->WriteHTML($table);
                $pdf->Output($name, 'D');

        }
        

            //$this->_redirect('*/*/');     
    }
    
    public function getManifest($incrementId) {
        $requestResult = $this->getApi()->getManifest();
        return $requestResult; 
    }
    
    /**
     * <p>Converts DPD op=pudo Opening Times into human readable format.</p>
     * <ul>
         <li><b>Input: 1:11:0:16:0,2:7:30:20:0,3:7:30:20:0,4:7:30:20:0,5:7:30:20:0,6:7:30:20:0,7:8:0:16:0</b></li>
         <li><b>Result: (E-R 7:30-20; L 8-16; P 11-16)</b></li>
     </ul>
     * 
     * @param string $dpdOpeningDescription DPD Openings description
     * @param Zend_Locale $locale Locale which is used to print out weekday names
     * @return string
     */
    public function getOpeningsDescriptionFromTerminal($dpdOpeningDescription, $locale = null) {
        $openingTimeFormat = 'H:m';
        $displayTimeFormat = 'H:mm';
        
        if (!$locale) {
            $locale = Mage::app()->getLocale()->getLocaleCode();
        }
        
        //days  start from monday
        $passThruOrder = array('2', '3', '4', '5', '6', '7', '1');
        
        /*
         * Format: array key = weekday name
         */
        $openingDescriptions = array();
        
        //we need these in order to get times in normalized manner
        $startTime = new Zend_Date(0, Zend_Date::TIMESTAMP);
        $endTime = new Zend_Date(0, Zend_Date::TIMESTAMP);
        
        //here are comma separeted opening times
        $openings = explode(',', $dpdOpeningDescription);
        /*
         * Format:
         * <weekday>:<starth>:<startm>:<endh>:<endm>
         * 1=sunday
         * 2=monday
         * ...
         * 7=saturday
         * 
         */
        foreach ($openings as $opening) {
            $openTimePartials = explode(':', $opening);
            $startTime->set($openTimePartials[1].':'.$openTimePartials[2], $openingTimeFormat);
            $endTime->set($openTimePartials[3].':'.$openTimePartials[4], $openingTimeFormat);
            
            if (!isset($openingDescriptions[(string)$openTimePartials[0]])) {
                $openingDescriptions[(string)$openTimePartials[0]] = array();
            }
            $openingDescriptions[(string)$openTimePartials[0]][] = str_replace(':00', '', $startTime->get($displayTimeFormat)) .'-'. str_replace(':00', '', $endTime->get($displayTimeFormat));
            
        }
        
        
        /*
         * Format:
         * array key = day of week digit
         * array value = all opening times for that day separated by comma
         */
        $finalOpeningDescriptions = array();
        $previusOpeningStatement = false;
        $previusWeekdayName = false;
        $firstElement = false;
        
        
        foreach ($passThruOrder as $dayOfWeekDigit) {
            $startTime->set($dayOfWeekDigit - 1, Zend_Date::WEEKDAY_DIGIT);

            $weekDayName = $startTime->get(Zend_Date::WEEKDAY_NARROW, $locale);
            if ($firstElement === false) {
                $firstElement = $previusWeekdayName;
            }
            if (isset($openingDescriptions[$dayOfWeekDigit])) {
                
                $openingStatement = str_replace('0-0', '0-24', implode(',', $openingDescriptions[$dayOfWeekDigit]));
            } else {
                $openingStatement = '';
            }
            
            if ($previusOpeningStatement !== false && $previusOpeningStatement != $openingStatement) {
                //we have a change
                if ($firstElement != $previusWeekdayName) {
                    $finalOpeningDescriptions[] = $firstElement.'-'.$previusWeekdayName.' '.$previusOpeningStatement;
                } else {
                    $finalOpeningDescriptions[] = $previusWeekdayName.' '.$previusOpeningStatement;
                }
                
                
                $firstElement = false;
            }
            $previusOpeningStatement = $openingStatement;
            $previusWeekdayName = $weekDayName;
            
        }
        if ($previusOpeningStatement !== false) {
            if ($previusOpeningStatement !== '') {
                //we have a change
                if (!$firstElement) {
                    $finalOpeningDescriptions[] = $previusWeekdayName . ' ' . $previusOpeningStatement;
                } else {
                    $finalOpeningDescriptions[] = $firstElement . '-' . $previusWeekdayName . ' ' . $previusOpeningStatement;
                }
            }
        }
        
        if (count($finalOpeningDescriptions)) {
            return '('.implode('; ', $finalOpeningDescriptions).')';
        }
        return '';
    }
    

/**
     * <p>Returns number or parcels for the order according to Maximum Package Weight defined in DPD settings</p>
     * @param Mage_Sales_Model_Order $order
     * @return int
     * @see Balticode_Postoffice_Helper_Data::getNumberOfPackagesFromItemWeights()
     */
    protected function _getNumberOfPackagesForOrder(Mage_Sales_Model_Order $order) {
        $productWeights = array();
        foreach ($order->getAllVisibleItems() as $orderItem) {
            /* @var $orderItem Mage_Sales_Model_Order_Item */
            for ($i = 0; $i < ($orderItem->getQtyOrdered() - $orderItem->getQtyRefunded()); $i++) {
                $productWeights[] = $orderItem->getWeight();
            }
            
        }
        $_code = Balticode_DpdLT_Model_Config::SHIPPING_METHOD_CODE_FLAT;
        return Mage::helper('balticode_postoffice')->getNumberOfPackagesFromItemWeights($productWeights,  Mage::getStoreConfig('carriers/'.$_code.'/max_package_weight'));
    }

}

