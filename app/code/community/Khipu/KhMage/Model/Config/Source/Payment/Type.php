<?php

/**
 * @category   Payment
 * @package    Khipu_KhMage
 * @copyright   Copyright (c) 2013 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author      khipu <developers@khipu.com>
 */
class Khipu_KhMage_Model_Config_Source_Payment_Type
{
    public function toOptionArray()
    {
        return array(
            array('value' => 0, 'label' => __('Both')),
            array('value' => 1, 'label' => __('Only simplified bank transfer')),
            array('value' => 2, 'label' => __('Only regular bank transfer')),
        );
    }
}
