<?php 


class AdminController extends AuthorizationController
{

    private $path = 'admin'. DIRECTORY_SEPARATOR ;

    public function __construct()
    {
        parent::__construct();
        if(!Request::isAdmin())
        {
            $indexController = new IndexController;
            $indexController -> index();
            exit;
        }
        
    }

    public function index()
    {
        $this -> view -> render($this->path.'adminTemplate');
    }

    ////////////////////////////
    /////////Categories/////////
    ////////////////////////////

    public function categories()
    {
        $categoriesClass = New Categories;
        $categories = $categoriesClass -> selectAll();
        $this -> view -> render($this->path.'adminCategories',[
            'categories' => $categories
        ]);
    }

    public function createCategories()
    {
        $categoriesClass = New Categories;
        $error="";
        if(isset($_POST['submit']))
        {
            $error = userhelper::nameError(trim($_POST['name']));
            if(empty($error))
            {
                $categoriesClass -> name = trim($_POST['name']);
                $categoriesClass -> create();
                Request::redirect(App::config('url'). 'admin/categories');
            }
        }
        $categories = $categoriesClass -> selectAll();
        $this -> view -> render($this->path.'adminCategories',[
            'error' => $error,
            'categories' => $categories
        ]);
    }

    public function updateCategories()
    {
        $categoriesClass = New Categories;
        $error="";
        if(isset($_POST['submitUpdate'])&& $_POST['selectCategories'] != "")
        {
            $error = userhelper::nameError(trim($_POST['nameUpdate']));
            if(empty($error))
            {
                $categoriesClass -> name = trim($_POST['nameUpdate']);
                $categoriesClass -> where = trim($_POST['selectCategories']);
                $categoriesClass -> update('id');
                Request::redirect(App::config('url'). 'admin/categories');
            }
        }
        $categories = $categoriesClass -> selectAll();
        $this -> view -> render($this->path.'adminCategories',[
            'errorUpdate' => $error,
            'categories' => $categories
        ]);
    }

    public function deleteCategories(array $parameters=[])
    {
        $categoriesClass = New Categories;
        $categoriesClass -> where = $parameters[0];
        $categoriesClass -> delete('id');
        $categories = $categoriesClass -> selectAll();
        $this -> categories();
    }

    ////////////////////////////
    ///////End Categories///////
    ////////////////////////////
}