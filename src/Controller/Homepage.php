<?php

declare(strict_types=1);

namespace App\Controller;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

final class Homepage extends AbstractController
{

    /**
     * @Route("/", name="homepage")
     *
     * @return Response
     */
    public function index(): Response
    {
        return $this->render('base.html.twig', []);
    }

    private function getForm(Request $request): FormInterface
    {
        $form = $this->createFormBuilder()
            ->add('value', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Type('string'),
                ]
            ])
            ->add('key', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Type('string'),
                ]
            ])
            ->add('submit', SubmitType::class, [])
            ->getForm();

        $form->handleRequest($request);

        return $form;
    }

    /**
     * @Route("/encode", name="encode", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function encode(Request $request): Response
    {
        $form = $this->getForm($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $key = Key::loadFromAsciiSafeString($form->get('key')->getData());
                $encoded = Crypto::encrypt($form->get('value')->getData(), $key);

                $this->addFlash('success', sprintf('<b>Encode string</b>: %s', $encoded));
            } catch (BadFormatException|EnvironmentIsBrokenException $e) {
                $this->addFlash('errors', $e->getMessage());
            }

        }

        return $this->render('base.html.twig', ['encodeForm' => $form->createView()]);
    }

    /**
     * @Route("/decode", name="decode", methods={"GET", "POST"})
     * @param Request $request
     * @return Response
     */
    public function decode(Request $request): Response
    {
        $form = $this->getForm($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $key = Key::loadFromAsciiSafeString($form->get('key')->getData());
                $encoded = Crypto::decrypt($form->get('value')->getData(), $key);

                $this->addFlash('success', sprintf('<b>Decode string</b>: %s', $encoded));
            } catch (BadFormatException|EnvironmentIsBrokenException $e) {
                $this->addFlash('errors', 'Key error: ' . $e->getMessage());
            } catch (WrongKeyOrModifiedCiphertextException $e) {
                $this->addFlash('errors', 'Decrypt error: ' . $e->getMessage());
            }

        }

        return $this->render('base.html.twig', ['decodeForm' => $form->createView()]);
    }
}