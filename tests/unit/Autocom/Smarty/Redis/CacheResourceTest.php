<?php

namespace Autocom\Smarty\Redis;

class CacheResourceTest extends \PHPUnit_Framework_TestCase {

  protected static $cachedContent;

  protected $client;
  protected $cacheResource;
  protected $smarty;

  public static function setUpBeforeClass() {
    $cachedContent = hex2bin('55fc0b92044f005c') . "<?php\n";
    $cachedContent .= <<<'HEADER'
/*%%SmartyHeaderCode:%%*/
if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
  ),
  'nocache_hash' => '',
  'tpl_function' => 
  array (
  ),
  'cache_lifetime' => 60,
  'unifunc' => 'content_55fc10ae4577b1_27857328',
  'has_nocache_code' => false,
  'version' => '3.1.27',
),true);
/*/%%SmartyHeaderCode%%*/
if ($_valid && !is_callable('content_55fc10ae4577b1_27857328')) {
function content_55fc10ae4577b1_27857328 ($_smarty_tpl) {
?>
content<?php }
}
?>
HEADER;
  }

  public function setUp() {
    $redisMockFactory = new \M6Web\Component\RedisMock\RedisMockFactory();
    $predisClientMock = $redisMockFactory->getAdapterClass('\Predis\Client', true);
    $this->client = new $predisClientMock();
    $this->client->reset();
    $this->cacheResource = new CacheResource($this->client);
    $this->smarty = new \Smarty();
    $this->smarty->registerCacheResource('redis', $this->cacheResource);
    $this->smarty->caching_type = 'redis';
  }

  public function testGetCachedContent() {
    $template = new \Smarty_Internal_Template('fake/template', $this->smarty, null, null, null, true, 60);
    $this->cacheResource->getCachedContent($template);
  }

  public function testWriteCachedContent() {
    $template = new \Smarty_Internal_Template('fake/template', $this->smarty, null, null, null, true, 60);
    $template->writeCachedContent('content');
  }

  public function testClearCache() {
    $template = new \Smarty_Internal_Template('fake/template', $this->smarty, null, null, null, true, 60);
    $template->clearCache();
  }

  public function testClearAllCache() {
    $this->smarty->clearAllCache();
  }

}  
