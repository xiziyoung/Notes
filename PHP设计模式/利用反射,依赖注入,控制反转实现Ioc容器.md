**反射:**   
PHP 5 开始 具有完整的反射 API，添加了对类、接口、函数、方法和扩展进行反向工程的能力。 此外，反射 API 提供了方法来取出函数、类和方法中的文档注释。
反射是一切框架的基础;     
参考 : https://www.php.net/manual/zh/book.reflection.php

相关设计模式:    
[控制反转](./PHP设计模式/控制反转.md) 和 [依赖注入](./PHP设计模式/依赖注入.md)

首先我们来下下面这个例子:    
在laravel框架中,很多对象实例通过方法参数定义就能传递进来，调用的时候不需要我们自己去手动传入对象实例。
类似这样:     
```php
// routes/web.php
Route::get('/post/store', 'PostController@store');

// App\Http\Controllers
class PostController extends Controller {

    public function store(Illuminate\Http\Request $request)
    {
        $this->validate($request, [
            'category_id' => 'required',
            'title' => 'required|max:255|min:4',
            'body' => 'required|min:6',
        ]);
    }

}
```
如上, 我们在调用控制器中store的时候, Request类的实例是什么时候传入的了, 我们并没有手动传入该对象实例;



下面来演示一下如何利用**反射**达到该目的:    
我们可以创建一个make方法，传入User，利用反射机制拿到User的构造函数，进而得到构造函数的参数对象。用递归的方式创建参数依赖。最后调用newInstanceArgs方法生成User实例。
```php
<?php
interface log
{
    public function write();
}

// 文件记录日志
class FileLog implements Log
{
    public function write(){
        echo 'file log write...';
    }
}

// 数据库记录日志
class DatabaseLog implements Log
{
    public function write(){
        echo 'database log write...';
    }
}


class User 
{
    protected $log;

    public function __construct(FileLog $log)
    {
        $this->log = $log;   
    }

    public function login()
    {
        // 登录成功，记录登录日志
        echo 'login success...';
        $this->log->write();
    }

}

function make($concrete){
    $reflector = new ReflectionClass($concrete);
    $constructor = $reflector->getConstructor();
    // 为什么这样写的? 主要是递归。比如创建FileLog不需要传入参数。
    if(is_null($constructor)) {
        return $reflector->newInstance();
    }else {
        // 构造函数依赖的参数
        $dependencies = $constructor->getParameters();
        // 根据参数返回实例，如FileLog
        $instances = getDependencies($dependencies);
        return $reflector->newInstanceArgs($instances);
    }

}

function getDependencies($paramters) {
    $dependencies = [];
    foreach ($paramters as $paramter) {
        $dependencies[] = make($paramter->getClass()->name);
    }
    return $dependencies;
}

$user = make('User');
$user->login();
```
这样我们每次就不用在记录用户日志时,还手动的new 一个Log类对象实例注入到User中了;   
但是我们上面的代码还是存在另外一个问题, 我们在用户类中写死了相关记录日志的类为FileLog。 如果某一天需要改用其他不同的方式记录日志,例如改为数据库记录,我们就得改User类构造函数的参数了。这样我们的代码就达不到解耦合的目的了；



**Ioc容器:**   
利用ioc容器可以达到为上面的代码解耦合的作用  
```php
<?php

interface Log
{
    public function write();
}

// 文件记录日志
class FileLog implements Log
{
    public function write(){
        echo 'file log write...';
    }
}

// 数据库记录日志
class DatabaseLog implements Log
{
    public function write(){
        echo 'database log write...';
    }
}

class User
{
    protected $log;
    public function __construct(Log $log)
    {
        $this->log = $log;
    }
    public function login()
    {
        // 登录成功，记录登录日志
        echo 'login success...';
        $this->log->write();
    }
}

class Ioc
{
    public $binding = [];

    public function bind($abstract, $concrete)
    {
        //这里为什么要返回一个closure呢？因为bind的时候还不需要创建User对象，所以采用closure等make的时候再创建FileLog;
        //闭包是指在创建时封装周围状态的函数，即使闭包所在的环境的不存在了，闭包中封装的状态依然存在。
        $this->binding[$abstract]['concrete'] = function ($ioc) use ($concrete) {//必须手动调用闭包对象的bindTo方法或使用use关键字把父作用域的变量及状态附加到PHP闭包中
            return $ioc->build($concrete);
        };
    }

    public function make($abstract)
    {
        $concrete = $this->binding[$abstract]['concrete'];
        /**
         * 当调用make()传参user时,此处$concrete相当于之前绑定的匿名函数:
         *   function ($ioc) use ('User') {
         *       return $ioc->build('User');
         *   };
         */
        /**
         * 当传参Log时,此处$concrete相当于之前绑定的匿名函数:
         *   function ($ioc) use ('FileLog') {
         *       return $ioc->build('FileLog');
         *   };
         */




        return $concrete($this);//此处才会执行之前的匿名函数
        /**
         * return $concrete($this)中的参数$this相当于给闭包传参类Ioc的实例 $ioc,
         *   function ($ioc) use ($concrete) {
         *       return $ioc->build($concrete);
         *   };
         */
    }

    // 创建对象
    public function build($concrete) {

        $reflector = new ReflectionClass($concrete);
        $constructor = $reflector->getConstructor();
        if(is_null($constructor)) {
            return $reflector->newInstance();
        }else {
            $dependencies = $constructor->getParameters();
            $instances = $this->getDependencies($dependencies);
            return $reflector->newInstanceArgs($instances);
        }
    }

    // 获取参数的依赖
    protected function getDependencies($paramters) {
        $dependencies = [];
        foreach ($paramters as $paramter) {
            $dependencies[] = $this->make($paramter->getClass()->name);
        }
        return $dependencies;
    }

}

//实例化IoC容器
$ioc = new Ioc();
$ioc->bind('Log','FileLog');
$ioc->bind('user','User');

$user = $ioc->make('user');
$user->login();
```

