<?php

namespace App\Http\Controllers;

use App\Models\Gameserver\Player;
use App\Models\Loginserver\AccountData;
use App\Models\Webserver\LogsAllopass;
use App\Models\Webserver\News;
use App\Models\Webserver\ShopCategory;
use App\Models\Webserver\ShopHistory;
use App\Models\Webserver\ShopItem;
use App\Models\Webserver\ShopSubCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AdminController extends Controller
{

    /**
     * GET /admin
     */
    public function index()
    {
        $shopHistoryToday = ShopHistory::where('created_at', '=', Carbon::today())->count();
        $shopHistoryTotal = ShopHistory::count();
        $accountsCount = AccountData::count();

        return view('admin.index', [
            'today'             => Carbon::today(),
            'shopHistoryToday'  => $shopHistoryToday,
            'shopHistoryTotal'  => $shopHistoryTotal,
            'accountsCount'     => $accountsCount
        ]);
    }

    /**
     * GET /admin/news
     */
    public function news()
    {
        return view('admin.news.index', [
            'news' => News::get()
        ]);
    }

    /**
     * GET /admin/news-delete/{id}
     */
    public function newsDelete($id)
    {
        News::destroy($id);
        return redirect()->back();
    }

    /**
     * GET/POST /admin/news-add/
     */
    public function newsAdd(Request $request)
    {
        $success    = null;
        $error      = null;

        if($request->isMethod('post')){

            $slug       = Str::slug($request->input('title'), '-');
            $article    = News::where('slug', '=', $slug)->first();

            if($article === null){
                News::create([
                    'title'         => $request->input('title'),
                    'slug'          => $slug,
                    'text'          => $request->input('content'),
                    'account_id'    => Session::get('user.id')
                ]);

                return redirect()->back()->with('success', "Votre article a été crée avec succès.");

            }
            else {
                return redirect()->back()->with('error', "Merci de changer le nom de votre article.")->withInput();
            }


        }

        return view('admin.news.add', [
            'error'     => $error,
            'success'   => $success
        ]);

    }

    /**
     * GET/POST /admin/news-edit/{id}
     */
    public function newEdit(Request $request, $id)
    {
        if($request->isMethod('get')) {
            $news = News::find($id)->first();

            return view ('admin.news.edit', [
                'news' => $news
            ]);

        }
        else {
            News::where('id', '=', $id)->update([
                'title'         => $request->input('title'),
                'slug'          => Str::slug($request->input('title'), '-'),
                'text'          => $request->input('content')
            ]);

            return redirect(route('admin.news'));
        }
    }

    /**
     * GET /admin/config
     */
    public function config(Request $request)
    {
        if($request->isMethod('post')) {
            $configs = $request->except('_token');

            foreach ($configs as $key => $value) {
                $keyReplace = str_replace('aion_', 'aion.', $key);
                Config::set($keyReplace, $value);
            }

        }

        return view('admin.config', [
            'configs' => Config::get('aion')
        ]);
    }

    /**
     * GET /admin/logs/{name}
     */
    public function logs($name)
    {
        $logsConfig      = Config::get('aion.logs');
        $logsPath        = $logsConfig['path'];
        $logsFiles       = $logsConfig['files'];
        $userAccessLevel = Session::get('user.access_level');

        foreach ($logsFiles as $key => $value) {

            // Check if the name are in the config
            if ($name.$value['extension'] == $value['file'].$value['extension']) {

                // Check User accessLevel
                if ($userAccessLevel >= $value['access_level']){

                    // Check if file exist
                    if (file_exists($logsPath.$value['file'].$value['extension'])){

                        $logContent = "";
                        $handle     = @fopen($logsPath.$value['file'].$value['extension'], "r");

                        if ($handle) {
                            while (($buffer = fgets($handle, 4096)) !== false) {
                                $logContent .= $buffer;
                            }
                            fclose($handle);
                        }

                        return view('admin.logs', [
                            'logName'      => $name,
                            'logExtension' => $value['extension'],
                            'logContent'   => $logContent
                        ]);

                    }

                }

            }
        }

        return redirect(route('admin'));
    }

    /**
     * GET/POST /admin/shop-category
     */
    public function shopCategory(Request $request)
    {
        // When try to add Category
        if($request->isMethod('post')) {
            ShopCategory::create([
                'category_name' => $request->input('category_name')
            ]);
        }

        $categories = ShopCategory::get();

        return view('admin.shop.category', [
            'categories' => $categories
        ]);
    }

    /**
     * GET/POST /admin/shop-subcategory
     */
    public function shopSubCategory(Request $request)
    {
        // When try to add SubCategory
        if($request->isMethod('post')) {
            ShopSubCategory::create([
                'id_category' => $request->input('category_id'),
                'name' => $request->input('sub_category_name')
            ]);
        }

        $categories             = ShopCategory::get();
        $categoriesSelectInput  = [];
        $subCategories          = ShopSubCategory::get();

        // Create beautiful array for select Input
        foreach($categories as $category){
            $categoriesSelectInput[$category->category_name] = [
              $category->id => $category->category_name
            ];
        }

        return view('admin.shop.subcategory', [
            'categories'    => $categoriesSelectInput,
            'subCategories' => $subCategories
        ]);
    }

    /**
     * POST /admin/search
     */
    public function search(Request $request)
    {
        $searchValue = $request->input('search_value');
        $searchType  = $request->input('search_type');

        switch ($searchType){
            case 'character':
                $results = Player::where('name', 'LIKE', '%'.$searchValue.'%')->paginate(15);
                break;
            case 'shop_item':
                $results = ShopItem::where('name', 'LIKE', '%'.$searchValue.'%')->paginate(15);
                break;
            default:
                $results = Player::where('name', 'LIKE', '%'.$searchValue.'%')->paginate(15);
                break;

        }

        return view('admin.search', [
            'searchType' => $searchType,
            'results'    => $results
        ]);

    }

    /**
     * GET /admin/shop-add
     */
    public function shopAdd(Request $request)
    {

        // Success message
        $success = null;

        // When try to add item
        if($request->isMethod('post')) {
            $itemAdded = ShopItem::create([
                'id_sub_category' => $request->input('id_sub_category'),
                'id_item'         => $request->input('id_item'),
                'name'            => $request->input('name'),
                'price'           => $request->input('price'),
                'quantity'        => $request->input('quantity'),
                'level'           => $request->input('level'),
            ]);

            if($itemAdded !== null){
                $success = $request->input('name')." a été ajouté avec succès";
            }
        }

        $subCategories      = ShopSubCategory::get();
        $subCategoriesInput = [];

        // Create beautiful array for select Input
        foreach($subCategories as $subCategory){
            $subCategoriesInput[$subCategory->name] = [
              $subCategory->id => $subCategory->name
            ];
        }

        return view('admin.shop.add', [
            'subCategories' => $subCategoriesInput,
            'success'       => $success
        ]);
    }

    /**
     * GET /admin/shop-edit/{id}
     */
    public function shopEdit(Request $request, $id)
    {
        // Success message
        $success = null;

        // When try to edit item
        if($request->isMethod('post')){
            $itemSaved = ShopItem::where('id_item', '=', $id)->update([
                'id_sub_category' => $request->input('id_sub_category'),
                'id_item'         => $request->input('id_item'),
                'name'            => $request->input('name'),
                'price'           => $request->input('price'),
                'quantity'        => $request->input('quantity'),
                'level'           => $request->input('level'),
            ]);

            if($itemSaved !== null){
                $success = $request->input('name')." a été modifié avec succès";
            }

        }

        $subCategories      = ShopSubCategory::get();
        $item               = ShopItem::where('id_item', '=', $id)->first();
        $subCategoriesInput = [];

        // Create beautiful array for select Input
        foreach($subCategories as $subCategory){
            $subCategoriesInput[$subCategory->name] = [
                $subCategory->id => $subCategory->name
            ];
        }

        return view('admin.shop.edit', [
            'item'          => $item,
            'subCategories' => $subCategoriesInput,
            'success'       => $success
        ]);
    }

    /**
     * GET /admin/allopass
     */
    public function allopass()
    {
        return view('admin.allopass', [
           'allopass' => LogsAllopass::orderBy('created_at', 'DESC')->get()
        ]);
    }

}