<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Chapter;
use App\Entity\ChapterImage;
use App\Entity\Product;
use App\Form\ChapterType;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductController extends AbstractController
{
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/', name: 'product_list')]
    public function listAction(ManagerRegistry $doctrine,Request $request): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();
        $categories = $doctrine->getRepository(Category::class)->findAll();

        return $this->render('product/index.html.twig', [
            'products' => $products, 'categories'=>$categories,
        ]);
    }



    #[Route('/product', name:'product')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $category = new Category();
        $category->setName('Computer Peripherals');


        $product = new Product();
        $product->setName('Keyboard');
        $product->setPrice(19.99);
        $product->setQuantity(2);
        $product->setDate(new DateTime(0-2-0));
        $product->setDescription('Ergonomic and stylish!');

        // relates this product to the category
        $product->setCategory($category);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($category);
        $entityManager->persist($product);
        $entityManager->flush();

        return new Response(
            'Saved new product with id: ' . $product->getId()
            . ' and new category with id: ' . $category->getId()
        );
    }
    /**
     * @Route("/insertUser", name="product")
     */
    public function insertAction(ManagerRegistry $doctrine): Response
    {
        $user= new User();

        $user->setEmail('abc@gmail.com');
        $user->setPassword("123@abc");
        // relates this product to the category

        $entityManager = $doctrine->getManager();
        $entityManager->persist($user);
        $entityManager->persist($user);
        $entityManager->flush();

        return new Response(
            'Saved new product with id: '.$user->getId()
            .' and new category with id: '.$user->getId()
        );
    }

    #[Route('/product/details/{id}', name: 'product_details')]
    public function detailsAction(ManagerRegistry $doctrine, $id, ProductRepository $productRepository)
    {
        $products = $doctrine->getRepository(Product::class)->find($id);
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $latestProducts = $productRepository->findLatestProducts();

        $totalViewCount = $products->getTotalViewCount();

        return $this->render('product/details.html.twig',['products'=>$products, 'categories'=>$categories , 'latestProducts'=>$latestProducts, 'totalViewCount' => $totalViewCount,]);
    }

    #[Route('/product/like/{id}', name: 'like_product')]
    public function likeProduct(Request $request, EntityManagerInterface $entityManager, Product $product): Response
    {
        // Kiểm tra xem user đã đăng nhập hay chưa
        if (!$this->getUser()) {
            // Nếu chưa đăng nhập, điều hướng tới trang đăng nhập
            return $this->redirectToRoute('app_login');
        }

        // Kiểm tra xem user đã thích sản phẩm chưa
        $user = $this->getUser();
        if (!$product->getLikedByUsers()->contains($user)) {
            // Nếu chưa thích, thêm user vào danh sách thích và tăng số lượt thích của sản phẩm lên 1
            $product->addLikedByUser($user);
            $product->setLikes($product->getLikes() + 1);
            $entityManager->flush();
        }

        // Sau khi thích thành công, điều hướng ngược lại trang sản phẩm
        return $this->redirectToRoute('product_details', ['id' => $product->getId()]);
    }

    #[Route('/product/delete/{id}', name: 'product_delete')]
    public function deleteAction(ManagerRegistry $doctrine,$id)
    {
        $em = $doctrine->getManager();
        $product = $em->getRepository(Product::class)->find($id);
        $em->remove($product);
        $em->flush();

        $this->addFlash(
            'error',
            'Product deleted'
        );
        return $this->redirectToRoute('product_list');
    }


    /**
     * @Route("/product/create", name="product_create", methods={"GET","POST"})
     */
    public function createAction(ManagerRegistry $doctrine,Request $request, SluggerInterface $slugger)
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // upload file
            $productImage = $form->get('productImage')->getData();
            if ($productImage) {
                $originalFilename = pathinfo($productImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $productImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $productImage->move(
                        $this->getParameter('productImages_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $product->setProductImage($newFilename);
            }else{
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }
            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash(
                'notice',
                'Product Added'
            );
            return $this->redirectToRoute('product_list');
        }
        return $this->renderForm('product/create.html.twig', ['form' => $form, 'categories'=>$categories]);
    }

    #[Route('/product/productByCat/{id}', name: 'productByCat')]
    public function productByCatAction(ManagerRegistry $doctrine, $id, ProductRepository $productRepository):Response
    {
        $category = $doctrine->getRepository(Category::class)->find($id);
        $products = $category->getProduct();
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $latestProducts = $productRepository->findLatestProducts();

        return $this->render('category/details.html.twig', ['products' => $products,
            'categories' => $categories, 'latestProducts'=>$latestProducts]);
    }

    #[Route('/product/edit/{id}', name: 'product_edit')]
    public function editAction(ManagerRegistry $doctrine, int $id,Request $request,SluggerInterface $slugger): Response{
        $entityManager = $doctrine->getManager();
        $product = $entityManager->getRepository(Product::class)->find($id);
        $form = $this->createForm(ProductType::class, @$product);
        $form->handleRequest($request);
        $categories = $doctrine->getRepository(Category::class)->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            //upload file
            $productImage = $form->get('productImage')->getData();
            if ($productImage) {
                $originalFilename = pathinfo($productImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $productImage->guessExtension();
                // Move the file to the directory where brochures are stored
                try {
                    $productImage->move(
                        $this->getParameter('productImages_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $product->setProductImage($newFilename);
            }else{
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }

            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();
            return $this->redirectToRoute('product_list', [
                'id' => $product->getId()
            ]);

        }
        return $this->renderForm('product/edit.html.twig', ['form' => $form, 'categories' => $categories,]);
    }

    /**
     * @Route("/search", name="product_search")
     */
    public function search(Request $request)
    {
        $keyword = $request->query->get('search');

        // Truy vấn cơ sở dữ liệu để tìm các sản phẩm phù hợp với từ khóa tìm kiếm (name hoặc description)
        $em = $this->entityManager;
        $products = $em->getRepository(Product::class)->createQueryBuilder('p')
            ->where('p.name LIKE :keyword OR p.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->getQuery()
            ->getResult();

        return $this->render('product/search_results.html.twig', [
            'products' => $products,
            'keyword' => $keyword,
        ]);
    }

    #[Route('/chapter/add', name: 'chapter_add')]
    public function addChapter(Request $request, EntityManagerInterface $entityManager, ManagerRegistry $doctrine, SluggerInterface $slugger): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();
        $chapter = new Chapter();
        $form = $this->createForm(ChapterType::class, $chapter);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $chapterImages_directory = $this->getParameter('chapterImages_directory');
            $files = $request->files->get('chapter')['chapterImage'];

            // Lưu thông tin chapter vào database trước để có chapter_id cho các chapter image
            $entityManager->persist($chapter);
            $entityManager->flush();

            foreach ($files as $file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $filename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                $file->move($chapterImages_directory, $filename);

                // Tạo mới đối tượng ChapterImage và lưu thông tin vào database
                $chapterImage = new ChapterImage();
                $chapterImage->setImagePath($filename);
                $chapterImage->setChapter($chapter);
                $entityManager->persist($chapterImage);
            }

            // Lưu tất cả các đối tượng ChapterImage vào database
            $entityManager->flush();

            // Chuyển hướng sau khi thêm chapter thành công
            return $this->redirectToRoute('product_list');
        }

        return $this->render('product/addchapter.html.twig', [
            'form' => $form->createView(),
            'products' => $products,
        ]);
    }
    #[Route('/chapter/details/{id}', name: 'chapter_details')]
    public function detailsChapter(ManagerRegistry $doctrine, $id, ProductRepository $productRepository, EntityManagerInterface $entityManager)
    {
        $chapter = $doctrine->getRepository(Chapter::class)->find($id);
        $chapterImages = $doctrine->getRepository(ChapterImage::class)->findAll();

        if (!$chapter) {
            throw $this->createNotFoundException('Chapter not found');
        }

        // Tăng số lượt view khi có một lượt click vào chapter
        $chapter->incrementViewCount();
        $entityManager->flush();


        return $this->render('product/chapter_details.html.twig',['chapter'=>$chapter, 'chapterImages'=>$chapterImages]);
    }

    #[Route('/donate', name: 'donate')]
    public function donate()
    {
        return $this->render('product/donate.html.twig');
    }







}
