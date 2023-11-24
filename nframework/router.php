<?
require 'include.php';

$loader1 = new \Twig\Loader\FilesystemLoader(__DIR__.'/templates');
$loader2= new \Twig\Loader\FilesystemLoader(__DIR__.'/templates/basic');
$loader = new \Twig\Loader\ChainLoader([$loader1, $loader2]);
$twig = new \Twig\Environment($loader, [
    'cache' => false//__DIR__.'/../compilation_cache',
]);


use Intervention\Image\ImageManager;
//https://github.com/alexdodonov/mezon-router#routing--


$router = new \Mezon\Router\Router();

$router->addRoute('index', function($route, $variables){
	global $_SERVER,$twig,$nframework,$config,$m;
	
	$header=$m->{$config['sitedb']}->pages->findOne(['path'=>'_header']);
	$footer=$m->{$config['sitedb']}->pages->findOne(['path'=>'_footer']);
	$parallax=$m->{$config['sitedb']}->pages->findOne(['path'=>'_parallax']);
	$menu=$m->{$config['sitedb']}->menus->findOne(['name'=>'_nav']);
	
	$nframework->usecommon=true;
	$template = $twig->load('page.html');
	
	if($config['homepagetype']=='page'){
		$page=$m->{$config['sitedb']}->pages->findOne(['path'=>'_home']);
		$nframework->metas['description']=$page->description;
		$nframework->metas['title']=$page->title;
		$nframework->metas['keywords']=$page->keywords;
			
	}else{
		
	}
	
	echo $template->render([
		'theme'=>$config['theme'],
		'parallaxpage' =>$parallax?->html,
		'page' =>$page->html,
		'header'=>$header->html,
		'footer'=>$footer->html,
		'menu'=>$menu->code,
		'route'=>'index.php'
	]);
},'GET');


$router->addRoute('/main.js', function(string $route,array $p){
	global $twig,$config;
	 header('Content-Type: text/javascript; charset=utf-8');
	$template = $twig->load('main.js');
	echo $template->render([
		'publicKey' => $config['notifications']['publicKey']
	]);
},'GET');
//TODO:favicon.ico


$router->addRoute('/robots.txt', function($route, $variables){
	global $_SERVER;
	header('Content-Type: text/plain');
echo'User-agent: *
Disallow: 
Disallow: /nframework/
Disallow: /account/
Sitemap: http://'.$_SERVER['HTTP_HOST'].'/sitemap.xml';	
});


$router->addRoute('/sitemap.xml', function($route, $variables){
	global $m,$config;
	header("Content-type: text/xml; charset=utf-8");
	$urls=[];
	foreach($m->{$config['sitedb']} as $url){
	$urls[]='<url>
  <loc>'.$url['url'].'</loc>
  <lastmod>'.$url['lastmod'].'</lastmod>
  <priority>'.$url['prioridad'].'</priority>
</url>';
	}
	echo'<?xml version="1.0" encoding="UTF-8"?>
<urlset
      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<!-- created by nframework5 -->
'.implode("\n",$urls).'

</urlset>';	
},'GET');


$router->addRoute('/.well-known/acme-challenge/[s:filename]', function($route, $variables){
	global $m,$config;
    $client = new Api($config->letsencrypt_email, __DIR__ . '/__account');
    $account = $client->account()->get();

	
	try {
    	$client->domainValidation()->start($account, $validationStatus[0], AuthorizationChallengeEnum::HTTP);
    	$privateKey = \Rogierw\RwAcme\Support\OpenSsl::generatePrivateKey();
		$csr = \Rogierw\RwAcme\Support\OpenSsl::generateCsr(['example.com'], $privateKey);
		if ($order->isReady() && $client->domainValidation()->allChallengesPassed($order)) {
    		$client->order()->finalize($order, $csr);
		}
		if ($order->isFinalized()) {
		   $certificateBundle = $client->certificate()->getBundle($order);
		}
		$config->letsencryptvalidtruh=strtotime('+90 days');
		

	} catch (DomainValidationException $exception) {
	    // The local HTTP challenge test has been failed...
	}
	foreach($validationData as $vd){
		if($vd['identifier']==$_SERVER['HTTP_HOST']&&$vd['filename']==$variables['filename']){
			echo $vd['content'];
			exit();
		}
	}
});


$router->addRoute('/images/config/[i:size]/logo.png', function(string $route,array $p){
	global $m,$config;
	$dir='img/nf/config/';
	$dst=$dir.'/logo_'.$p['size'].'.png';
	if(!file_exists($dst)||filemtime($dst)<filemtime($config['image'])){
		if(!file_exists($dir)){
			mkdir($dir,0777,true);
		}
		$manager = new ImageManager(array('driver' => 'gd'));
		$img = $manager->make($config['image']);
		$img->fit($p['size'],$p['size'], function ($constraint) {
		    $constraint->aspectRatio();
		   // $constraint->upsize();
		});
		$img->save($dst);
	}
	header('Content-Length: '.filesize($dst));
    header('Content-Type: image/png');
    echo file_get_contents($dst);
},'GET');
$router->addRoute('/images/config/[i:w]/[i:h]/logo.png', function(string $route,array $p){
	global $m,$config;
	$dir='img/nf/config/';
	$dst=$dir.'/logo_'.$p['w'].'x'.$p['h'].'.png';
	if(!file_exists($dst)||filemtime($dst)<filemtime($config['image'])){
		if(!file_exists($dir)){
			mkdir($dir,0777,true);
		}
		$manager = new ImageManager(array('driver' => 'gd'));
		$img = $manager->make($config['image']);
		$img->fit($p['w'], $p['h'], function ($constraint) {
		    $constraint->aspectRatio();
		    //$constraint->upsize();
		});
		$img->save($dst);
	}
	header('Content-Length: '.filesize($dst));
    header('Content-Type: image/png');
    echo file_get_contents($dst);
},'GET');

$router->addRoute('/images/resize/[s:id]/[i:w]/[i:h]/[s:file]', function(string $route,array $p){
	if(isset($_SESSION['imagesresize'][$p['id']])){
	$conf=$_SESSION['imagesresize'][$p['id']];
		$filename=$p['file'];
		$pos = strrpos($filename, '.');
		$name=substr($filename,0,$pos);
		$ext=substr($filename,$pos);
		$dst=$conf['dst'].'/'.$name.'._'.$p['w'].'x'.$p['h'].$ext;
		$src=$conf['src'].'/'.$filename;
		//echo "$name  $ext $dst";
		if(!file_exists($dst)||filemtime($dst)<filemtime($src)){
			if(!file_exists($conf['dst'])){
				mkdir($dir,0777,true);
			}
			$manager = new ImageManager(array('driver' => 'gd'));
			if(!file_exists($src)){
				$src=$conf['default'];
			}
			$img = $manager->make($src);
			$img->fit($p['w'], $p['h'], function ($constraint) {
			    $constraint->aspectRatio();
			    //$constraint->upsize();
			});
			$img->save($dst);
		}
		header('Content-Length: '.filesize($dst));
	    header('Content-Type: image/png');
	    echo file_get_contents($dst);//*/
	}
},'GET');



$router->addRoute('/nf.webmanifest', function(string $route,array $p){
	global $config;
	header('Content-Type: application/manifest+json; charset=utf-8');
echo '{
    "name": "'.$config['title'].'",
    "short_name": "'.$config['shortname'].'",
    "theme_color": "'.$config['manifest']['theme_color'].'",
    "background_color": "'.$config['manifest']['background_color'].'",
    "display": "standalone",
    "scope": "/",
    "start_url": "/",
    "description": "'.$config['description'].'",
    "orientation": "any",
    "icons": [
        {
            "src": "https://'.$_SERVER['HTTP_HOST'].'/images/config/72/logo.png",
            "sizes": "72x72",
            "type": "image/png",
            "purpose":"any"
        },
        {
            "src": "https://'.$_SERVER['HTTP_HOST'].'/images/config/96/logo.png",
            "sizes": "96x96",
            "type": "image/png",
            "purpose":"any"
        },
        {
            "src": "https://'.$_SERVER['HTTP_HOST'].'/images/config/144/logo.png",
            "sizes": "144x144",
            "type": "image/png",
            "purpose":"any"
        },
        {
            "src": "https://'.$_SERVER['HTTP_HOST'].'/images/config/192/logo.png",
            "sizes": "192x192",
            "type": "image/png",
            "purpose":"any"
        },
        {
            "src": "https://'.$_SERVER['HTTP_HOST'].'/images/config/256/logo.png",
            "sizes": "256x256",
            "type": "image/png",
            "purpose":"any"
        },
        {
            "src": "https://'.$_SERVER['HTTP_HOST'].'/images/config/384/logo.png",
            "sizes": "384x384",
            "type": "image/png",
            "purpose":"any"
        },
        {
            "src": "https://'.$_SERVER['HTTP_HOST'].'/images/config/512/logo.png",
            "sizes": "512x512",
            "type": "image/png",
            "purpose":"any"
        },
        {
            "src": "https://'.$_SERVER['HTTP_HOST'].'/images/config/1024/logo.png",
            "sizes": "1024x1024",
            "type": "image/png",
            "purpose":"any"
        }
    ]
}';
//72, 96, 144, 192, 256, 384, 512

},'GET');

$router->addRoute('/getPayload', function(string $route,array $p){
	global $m,$config;
	if(!empty($_GET['endpoint'])){
		if($_GET['endpoint']!='null'){
		$m->{$config['sitedb']}->endpoints->updateOne([['_id'=>(string)session_id()]],
			[
			'$set'=>[
				'endpoint'=>json_decode($_GET['endpoint'])
				]
			],['upsert'=> true]);
		}else{
			$m->{$config['sitedb']}->endpoints->deleteOne([['_id'=>(string)session_id()]]);
		}
		echo "ok";
	}
});

$router->addRoute('/privacidad', function(string $route,array $p){
	global $nframework,$twig,$config;
	$page=$m->{$config['sitedb']}->pages->findOne(['title'=>'Privacidad']);
	echo $page['html'];
},'GET');

$router->addRoute('/sw.js', function(string $route,array $p){
	global $nframework,$twig,$config;
	$template = $twig->load('sw.js');
	header('Content-Type: application/javascript; charset=utf-8');
	echo $template->render([
		'publicKey' => $config['notifications']['publicKey'],
		'tocache'=>array_values(array_merge($nframework->csss,$nframework->jss)),
		'csss'=>implode ("','",$nframework->csss),
		'jss'=>implode ("','",$nframework->jss)
	]);
},'GET');

foreach($m->{$config['sitedb']}->pages->distinct('path') as $d){
	if(!empty($d)){
		$router->addRoute($d, function($route,$arg){
			global $m,$config,$nframework,$twig;
			$page=$m->{$config['sitedb']}->pages->findOne(['path'=>$route]);
			$nframework->metas['description']=$page->description;
			$nframework->metas['title']=$page->title;
			$nframework->metas['keywords']=$page->keywords;
			
			$menu=$m->{$config['sitedb']}->menus->findOne(['name'=>'_nav']);
			$header=$m->{$config['sitedb']}->pages->findOne(['path'=>'_header']);
			$footer=$m->{$config['sitedb']}->pages->findOne(['path'=>'_footer']);
			$nframework->usecommon=true;
			$template = $twig->load('page.html');
			echo $template->render([
				'theme'=>$config['theme'],
				'page' => $page->html,
				'header'=>$header->html,
				'footer'=>$footer->html,
				'route' => $route,
				'menu'=>$menu->code,
			]);
			//echo $page->html;
		}, 'GET'); // this handler will be called for POST requests
	}
}
$router->addRoute('/cachetest.png', function($route,$arg){
	global $m;
	//$developermode=true;
	$cache=new cache(__DIR__.'/profilepict.png');
	$cache->contentType='image/png';
	$cache->cache();
	
});
