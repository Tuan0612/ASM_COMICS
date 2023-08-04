<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\CategoryType;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Repository\CategoryRepository;
class CategoryController extends AbstractController
{

    #[Route('/category/view/{id}', name: 'category_view')]
    public function viewCategory( $id, ManagerRegistry $doctrine): Response
    {
        $category_id = $doctrine->getManager();
        $categories = $category_id->getRepository(Category::class)->find($id);
        $products = $categories->getProduct();


        return $this->render('category/view.html.twig',['products' => $products,'categories'=>$categories]);
    }




    #[Route('/category/details', name: 'category_details')]
    public function detailsCategory(ManagerRegistry $doctrine, ProductRepository $productRepository): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $latestProducts = $productRepository->findLatestProducts();

        return $this->render('category/details.html.twig', [
            'products' => $products, 'categories'=>$categories, 'latestProducts'=>$latestProducts
        ]);
    }

    #[Route('/category/delete/{id}', name: 'category_delete')]
    public function deleteCategory(ManagerRegistry $doctrine,$id)
    {
        $em = $doctrine->getManager();
        $categories = $em->getRepository(Category::class)->find($id);
        $em->remove($categories);
        $em->flush();

        $this->addFlash(
            'error',
            'Category deleted'
        );
        return $this->redirectToRoute('product_list');
    }



    /**
     * @Route("/category/create", name="category_create", methods={"GET","POST"})
     */
    public function createCategory(ManagerRegistry $doctrine,Request $request)
    {

        $products = $doctrine->getRepository(Category::class)->findAll();
        $categories = new Category();
        $form = $this->createForm(CategoryType::class, $categories);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $doctrine->getManager();
            $em->persist($categories);
            $em->flush();

            $this->addFlash(
                'notice',
                'Category Added'
            );

            return $this->redirectToRoute('category_details', [
                'id' => $categories->getId()
            ]);

        }

        return $this->renderForm('category/create.html.twig', ['form' => $form, 'categories'=>$categories, '$products'=>$products]);
    }

    /**
     * @Route("/category/edit/{id}", name="category_edit")
     */
    public function editAction(ManagerRegistry $doctrine, int $id,Request $request): Response{
        $entityManager = $doctrine->getManager();
        $categories = $entityManager->getRepository(Category::class)->find($id);
        $form = $this->createForm(CategoryType::class, @$categories);
        $form->handleRequest($request);
        $products = $doctrine->getRepository(Category::class)->findAll();
        if ($form->isSubmitted() && $form->isValid()) {

            $em = $doctrine->getManager();
            $em->persist($categories);
            $em->flush();
            return $this->redirectToRoute('category_details', [
                'id' => $categories->getId()
            ]);

        }
        return $this->renderForm('category/edit.html.twig', ['form' => $form,'categories'=>$categories, '$products'=>$products]);
    }

}
