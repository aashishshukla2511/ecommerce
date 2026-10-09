<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Flash;
use App\Helpers\Url;
use App\Services\ProductService;

class ProductController extends Controller
{
    private ProductService $productService;

    public function __construct(?ProductService $productService = null)
    {
        $this->productService = $productService ?? new ProductService();
    }

    public function adminIndex(Request $request): Response
    {
        $search = $request->get('q');
        $page = (int)$request->get('page', 1);

        $result = $this->productService->listProducts(false, $search, $page, 15);

        return $this->render('admin/products/index', array_merge($result, [
            'title' => 'Manage Products',
        ]), 'layouts/admin');
    }

    public function adminCreate(Request $request): Response
    {
        return $this->render('admin/products/create', [
            'title' => 'Create Product',
            'errors' => Flash::get('errors'),
        ], 'layouts/admin');
    }

    public function adminStore(Request $request): Response
    {
        $data = [
            'name' => $request->post('name', ''),
            'sku' => $request->post('sku', ''),
            'description' => $request->post('description', ''),
            'price' => $request->post('price', ''),
            'stock_quantity' => $request->post('stock_quantity', ''),
            'is_active' => $request->post('is_active', '0') === '1' ? 1 : 0,
        ];

        $result = $this->productService->createProduct($data);

        if (!$result['success']) {
            Flash::setOld($data);
            Flash::set('errors', $result['errors']);
            Flash::error("Please fix the validation errors below.");
            Url::redirect('/admin/products/create');
            exit;
        }

        Flash::clearOld();
        Flash::success("Product '{$result['product']['name']}' created successfully.");
        Url::redirect('/admin/products');
        exit;
    }

    public function adminShow(Request $request, string $id): Response
    {
        $productId = (int)$id;
        $product = $this->productService->getProduct($productId, false);

        return $this->render('admin/products/show', [
            'title' => 'Product Details: ' . $product['name'],
            'product' => $product,
        ], 'layouts/admin');
    }

    public function adminEdit(Request $request, string $id): Response
    {
        $productId = (int)$id;
        $product = $this->productService->getProduct($productId, false);

        return $this->render('admin/products/edit', [
            'title' => 'Edit Product: ' . $product['name'],
            'product' => $product,
            'errors' => Flash::get('errors'),
        ], 'layouts/admin');
    }

    public function adminUpdate(Request $request, string $id): Response
    {
        $productId = (int)$id;
        $data = [
            'name' => $request->post('name', ''),
            'sku' => $request->post('sku', ''),
            'description' => $request->post('description', ''),
            'price' => $request->post('price', ''),
            'stock_quantity' => $request->post('stock_quantity', ''),
            'is_active' => $request->post('is_active', '0') === '1' ? 1 : 0,
        ];

        $result = $this->productService->updateProduct($productId, $data);

        if (!$result['success']) {
            Flash::setOld($data);
            Flash::set('errors', $result['errors']);
            Flash::error("Please fix the validation errors below.");
            Url::redirect("/admin/products/{$productId}/edit");
            exit;
        }

        Flash::clearOld();
        Flash::success("Product '{$result['product']['name']}' updated successfully.");
        Url::redirect('/admin/products');
        exit;
    }

    public function adminDeactivate(Request $request, string $id): Response
    {
        $productId = (int)$id;
        $this->productService->deactivateProduct($productId);

        Flash::success("Product deactivated successfully.");
        Url::redirect('/admin/products');
        exit;
    }

    public function customerIndex(Request $request): Response
    {
        $search = $request->get('q');
        $page = (int)$request->get('page', 1);

        $result = $this->productService->listProducts(true, $search, $page, 12);

        return $this->render('products/index', array_merge($result, [
            'title' => 'Browse Products',
        ]));
    }

    public function customerShow(Request $request, string $id): Response
    {
        $productId = (int)$id;
        $product = $this->productService->getProduct($productId, true);

        return $this->render('products/show', [
            'title' => $product['name'],
            'product' => $product,
        ]);
    }
}
