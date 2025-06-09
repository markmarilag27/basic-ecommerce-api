<?php

namespace App\Controller;

use App\DTO\ProductInput;
use App\Entity\Product;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products', name: 'api_products_')]
final class ProductController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ProductRepository $productRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): Response
    {
        $products = $this->productRepository->findAll();

        $data = $this->serializer->serialize($products, 'json');

        return new Response($data, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        $data = $this->serializer->serialize($product, 'json');
        return new Response($data, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $productInput = $this->serializer->deserialize($request->getContent(), ProductInput::class, 'json');

        $errors = $this->validator->validate($productInput);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $violation) {
                $errorMessages[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errorMessages], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Uniqueness check for product name
        $existingProduct = $this->productRepository->findOneBy(['name' => $productInput->name]);
        if ($existingProduct) {
            return $this->json([
                'success' => false,
                'errors' => [
                    'name' => 'A product with this name already exists.'
                ]
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product
            ->setName($productInput->name)
            ->setDescription($productInput->description)
            ->setPrice($productInput->price)
            ->setQuantity($productInput->quantity)
            ->setImageUrl($productInput->imageUrl)
            ->setCreatedAt(new DateTimeImmutable());

        $this->em->persist($product);
        $this->em->flush();

        return $this->json(['message' => 'Product created successfully'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Product $product): Response
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        // Update only fields provided
        $product
            ->setName($data['name'] ?? $product->getName())
            ->setDescription(array_key_exists('description', $data) ? $data['description'] : $product->getDescription())
            ->setPrice($data['price'] ?? $product->getPrice())
            ->setQuantity($data['quantity'] ?? $product->getQuantity())
            ->setImageUrl($data['imageUrl'] ?? $product->getImageUrl())
            ->setUpdatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($product);

        if (count($errors) > 0) {
            return $this->json([
                'success' => false,
                'errors' => (string) $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->em->flush();

        $json = $this->serializer->serialize($product, 'json');

        return new Response($json, Response::HTTP_OK, [
            'Content-Type' => 'application/json',
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Product $product): Response
    {
        $this->em->remove($product);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
