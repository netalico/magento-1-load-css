<?php

class Netalico_CSSPreload_Block_Preload extends Mage_Page_Block_Html_Head
{
    const PATTERN_ATTRS = ':attributes:';
    const PATTERN_URL   = ':path:';
    const LINK_TEMPLATE = '<link rel="preload" as="style" href=":path:" onload="this.rel=\'stylesheet\'" :attributes: />';

    protected function &_prepareStaticAndSkinElements($format, array $staticItems, array $skinItems,
                                                      $mergeCallback = null)
    {
        $shouldMergeCss = Mage::getStoreConfigFlag('dev/css/merge_css_files');
        $enableCSSPreload = Mage::getStoreConfigFlag('dev/css/csspreload');
        if(!$shouldMergeCss || !$enableCSSPreload) {
            return parent::_prepareStaticAndSkinElements($format, $staticItems, $skinItems, $mergeCallback);
        }

        $designPackage = Mage::getDesign();
        $baseJsUrl = Mage::getBaseUrl('js');
        $items = array();
        if ($mergeCallback && !is_callable($mergeCallback)) {
            $mergeCallback = null;
        }

        // get static files from the js folder, no need in lookups
        foreach ($staticItems as $params => $rows) {
            foreach ($rows as $name) {
                $items[$params][] = $mergeCallback ? Mage::getBaseDir() . DS . 'js' . DS . $name : $baseJsUrl . $name;
            }
        }

        // lookup each file basing on current theme configuration
        foreach ($skinItems as $params => $rows) {
            foreach ($rows as $name) {
                $items[$params][] = $mergeCallback ? $designPackage->getFilename($name, array('_type' => 'skin'))
                    : $designPackage->getSkinUrl($name, array());
            }
        }

        $html = '';
        foreach ($items as $params => $rows) {
            // attempt to merge
            $mergedUrl = false;
            if ($mergeCallback) {
                $mergedUrl = call_user_func($mergeCallback, $rows);
            }
            // render elements
            $params = trim($params);
            $params = $params ? ' ' . $params : '';
            if ($mergedUrl) {
                if(substr($mergedUrl, -4) == '.css') {
                    $html .= $this->renderLinkTemplate($mergedUrl, $params);
                } else {
                    $html .= sprintf($format, $mergedUrl, $params);
                }
            } else {
                foreach ($rows as $src) {
                    $html .= sprintf($format, $src, $params);
                }
            }
        }
        return $html;
    }

    private function renderLinkTemplate($assetUrl, $additionalAttributes)
    {
        return str_replace(
            [self::PATTERN_URL, self::PATTERN_ATTRS],
            [$assetUrl, $additionalAttributes],
            self::LINK_TEMPLATE
        );
    }
}