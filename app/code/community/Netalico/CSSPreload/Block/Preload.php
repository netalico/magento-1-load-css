<?php

class Netalico_CSSPreload_Block_Preload extends Mage_Core_Block_Template
{
    const PATTERN_ATTRS = ':attributes:';
    const PATTERN_URL   = ':path:';

    protected function _toHtml()
    {
        $html = '';
        $assets = $this->getAssets();
        $designPackage = Mage::getDesign();

        if (empty($assets)) {
            return "\n<!-- CSS Preload: No assets provided -->\n";
        }

        if (!$this->hasLinkTemplate()) {
            return "\n<!-- CSS Preload: No template defined -->\n";
        }

        $shouldMergeCss = Mage::getStoreConfigFlag('dev/css/merge_css_files');
        if($shouldMergeCss) {
            $assetUrls = array();
            foreach ($assets as $asset) {
                foreach($asset['attributes'] as $attributeName => $attributeValue) {
                    if($attributeName == 'media') {
                        if(!isset($assetUrls[$attributeValue])) {
                            $assetUrls[$attributeValue] = array();
                        }
                        $assetUrls[$attributeValue][] = $designPackage->getFilename($asset['path'], array('_type' => 'skin'));
                    }
                }
            }
            foreach($assetUrls as $media => $urls) {
                $mergedUrl = call_user_func(array(Mage::getDesign(), 'getMergedCssUrl'), $urls);
                $html .= $this->renderLinkTemplate($mergedUrl, 'media="'.$media.'"');
            }
        } else {
            foreach ($assets as $asset) {
                $attributesHtml = array();
                foreach($asset['attributes'] as $attributeName => $attributeValue) {
                    $attributesHtml[] = sprintf('%s="%s"', $attributeName, $attributeValue);
                }

                $assetUrl = $this->getSkinUrl($asset['path']);
                $html .= $this->renderLinkTemplate($assetUrl, implode(' ',$attributesHtml));
            }
        }

        return $html;
    }

    private function renderLinkTemplate($assetUrl, $additionalAttributes)
    {
        return str_replace(
            [self::PATTERN_URL, self::PATTERN_ATTRS],
            [$assetUrl, $additionalAttributes],
            $this->getLinkTemplate()
        );
    }
}