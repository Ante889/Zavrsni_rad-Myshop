<?php 


class AdminProductsController extends Controller
{
    private $path = 'admin'. DIRECTORY_SEPARATOR . 'products'. DIRECTORY_SEPARATOR ;

    public function __construct()
    {
        parent::__construct();
        if(!Request::isAdmin())
        {
            Request::redirect(App::config('url'));
        }
    }

    public function index()
    {
        $limit=10;
        $offset=0;
        $errorDiscount ='';
        if(isset($_POST['setDiscount']))
        {
            $discount = Request::issetTrim('setDiscount');
            $errorDiscount = producthelper::discountError($discount);
            if(empty($errorDiscount))
            {
                $this->allProductDiscount($discount);
            }
        }

        if(!empty($_GET['page'])){
            $offset = ($limit * $_GET['page']) - $limit;
            $page = $_GET['page'];
        }else{
            $page = 1;
        }

        $productsClass = New Products;
        if(isset($_POST['submit']) && !empty($_POST['title']))
        {
            $ProductsNumber = count($productsClass -> selectAllLike("%".trim($_POST['title'])."%",'title'));
            $products= $productsClass -> selectAllLikeLimit("%".trim($_POST['title'])."%",'title',$limit,$offset);
            $pathForPager = 'AdminProducts/search='. $_POST['title'] .'?page=';
        }else
        {
            $ProductsNumber = count($productsClass -> selectAll());
            $products = $productsClass -> selectAllLimit($limit,$offset);
            $pathForPager = 'AdminProducts?page=';
        }  
        $this -> view -> render($this->path.'adminProducts',[
            'errorDiscount' => $errorDiscount,
            'products' => $products,
            'pagination' =>[
                'itemsNumber' => ceil($ProductsNumber/$limit),
                'maxPage' => ceil($ProductsNumber/$limit) - (ceil($ProductsNumber/$limit)-$page) + 2,
                'path' => $pathForPager,
                'page' => $page
            ],
        ]);
    }

    public function createProducts()
    {
        $categories= new Categories;
        $categories= $categories -> selectAll();
        $SuccessMsg='';
        $errors= [
            'title' => '',
            'author' => '', 
            'image' => '',
            'price' => '',
            'category' => '', 
            'content' => '', 
            'pdf' => ''
        ];
        $title = Request::issetTrim('title');
        $author = Request::issetTrim('author');
        $image = isset($_FILES['image']) ? $_FILES['image'] : '';
        $price = Request::issetTrim('price');
        $category = Request::issetTrim('category');
        $content = Request::issetTrim('content');
        $pdf = isset($_FILES['pdf']) ? $_FILES['pdf'] : '';
        $discount = Request::issetTrim('discount');
        if($image != ''){
            $imageName = uniqid().basename($image['name']);
        }else 
        {
            $imageName ='';
        }
        if($pdf != ''){
            $pdfName = uniqid().'-'.basename($pdf['name']);
        }else 
        {
            $pdfName ='';
        }

        if(isset($_POST['submit'])){
            
            $errors['title'] = producthelper::basicError($title);
            $errors['author'] = producthelper::basicError($author);
            $errors['image'] = producthelper::photoError($image);
            $errors['price'] = producthelper::numbersError($price);
            $errors['category'] = producthelper::categoryError($category);
            $errors['content'] = producthelper::basicError($content);
            $errors['pdf'] = producthelper::photoError($pdf);      
            $errors['discount'] = producthelper::discountError($discount); 
           
            //Create product
    
            if(empty($errors['title']) && empty($errors['author']) && empty($errors['image']) && empty($errors['price'])&& empty($errors['category'])&& empty($errors['content'])&& empty($errors['pdf']) && empty($errors['discount'])){
                $ProductsClass = new Products;
                $ProductsClass -> title = $title;
                $ProductsClass -> author = $author;
                $ProductsClass -> image = $imageName;
                $ProductsClass -> price = $price;
                $ProductsClass -> category = $category;
                $ProductsClass -> content = $content;
                $ProductsClass -> pdf = $pdfName;
                $ProductsClass -> creation_time= time();
                $ProductsClass -> discount = $discount;
                $ProductsClass -> create();
                $SuccessMsg= 'Product has been successfully created';
                move_uploaded_file($image['tmp_name'], IMAGE_PATH .$imageName);
                move_uploaded_file($pdf['tmp_name'], PDF_PATH .$pdfName);
            }
        }
        $this -> view -> render($this->path.'adminProductsAdd',[
            'categories' => $categories,
            'errors' => $errors,
            'succesMsg' => $SuccessMsg,
            'returnField' => [
                'title' => $title,
                'author' => $author, 
                'image' => $imageName,
                'price' => $price,
                'category' => $category, 
                'content' => $content, 
                'pdf' => $pdf,
                'discount' => $discount
            ]
        ]);
    }

    public function updateProducts(array $parameters=[])
    {
        $categories= new Categories;
        $categories= $categories -> selectAll();
        $errors= [
            'title' => '',
            'author' => '', 
            'image' => '',
            'price' => '',
            'category' => '', 
            'content' => '', 
            'pdf' => '',
            'discount' => ''
        ];

        $ProductsClass = new Products;
        $ProductsClass -> where= $parameters[0];
        $Fields = $ProductsClass-> select('id')[0];
        if(empty($Fields)){
            Request::redirect(App::config('url'). 'AdminProducts');
            return;
        }
        $image = isset($_FILES['image']) ? $_FILES['image'] : '';
        $pdf = isset($_FILES['pdf']) ? $_FILES['pdf'] : '';

        if(isset($_POST['submit'])){

        $errors['title'] = producthelper::basicError(Request::issetTrim('title'));
        $errors['author'] = producthelper::basicError(Request::issetTrim('author'));
        $errors['image'] = producthelper::photoError($image);
        $errors['price'] = producthelper::numbersError(Request::issetTrim('price'));
        $errors['category'] = producthelper::numbersError(Request::issetTrim('category'));
        $errors['content'] = producthelper::basicError(Request::issetTrim('content'));
        $errors['pdf'] = producthelper::photoError($pdf);
        $errors['discount']= producthelper::discountError(Request::issetTrim('discount'));
        if(empty($image['name'])){
            unset($errors['image']);
        }
        if(empty($pdf['name'])){
            unset($errors['pdf']);
        }

        if(empty($errors['title']) && empty($errors['author']) && empty($errors['image']) && empty($errors['price'])&& empty($errors['category'])&& empty($errors['content'])&& empty($errors['pdf']) && empty($errors['discount'])){
        
            if(!empty($image['name'])){
                $imageName = uniqid().basename($image['name']);
                unlink(IMAGE_PATH . $Fields-> image);
                move_uploaded_file($image['tmp_name'], IMAGE_PATH .$imageName);
            }else 
            {
                $imageName =$Fields-> image;
            }
            if(!empty($pdf['name'])){
                $pdfName = uniqid().'-'.basename($pdf['name']);
                move_uploaded_file($pdf['tmp_name'], PDF_PATH .$pdfName);
            }else 
            {
                $pdfName =$Fields-> pdf;
            }

            $ProductsClass -> title = Request::issetTrim('title');
            $ProductsClass -> author = Request::issetTrim('author');
            $ProductsClass -> image =  $imageName;
            $ProductsClass -> price = Request::issetTrim('price');
            $ProductsClass -> category = Request::issetTrim('category');
            $ProductsClass -> content = Request::issetTrim('content');
            $ProductsClass -> pdf = $pdfName;
            $ProductsClass -> discount = Request::issetTrim('discount');
            $ProductsClass -> where = $parameters[0];
            $ProductsClass -> update('id');
            Request::redirect(App::config('url'). 'AdminProducts/updateProducts/'. $parameters[0]);
        }
    }
        $this -> view -> render($this->path.'adminProductsUpdate',[
            'categories' => $categories,
            'errors' => $errors,
            'returnField' => [
              'id' => $parameters[0],
              'title' => $Fields -> title,
              'author' => $Fields -> author, 
              'image' => $Fields ->image,
              'price' => $Fields ->price,
              'category' => $Fields ->category, 
              'content' => $Fields ->content, 
              'pdf' => $Fields ->pdf,
              'discount' => $Fields->discount
            ]
        ]);
    }

    public function deleteProducts(array $parameters=[])
    {
        $products = userhelper::shortSelect('Products','id',$parameters[0]);
        unlink(IMAGE_PATH . $products-> image);
        unlink(PDF_PATH . $products-> pdf);
        $productsClass = New Products;
        $productsClass -> where = $parameters[0];
        $productsClass -> delete('id');
        Request::redirect(App::config('url'). 'AdminProducts');
    }

    public function allProductDiscount($discount)
    {
        $productClass= new Products;
        $products = $productClass -> selectAll();
        foreach ($products as $key) {
            $productClass -> discount = $discount;
            $productClass -> where = $key->id;
            $productClass -> update('id');
        }
    }

    public function removeDiscount()
    {
        $productClass= new Products;
        $products = $productClass -> selectAll();
        foreach ($products as $key) {
            $productClass -> discount = '%';
            $productClass -> where = $key->id;
            $productClass -> update('id');
        }
        Request::redirect(App::config('url'). 'AdminProducts');
    }
}