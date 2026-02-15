<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class FaceLoginController extends AbstractController
{
    #[Route('/login/face', name: 'login_face', methods: ['POST'])]
    public function loginFace(Request $request, UserRepository $userRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if(!$descriptor) {
            return new JsonResponse(['success' => false, 'message' => 'Descripteur manquant.']);
        }

        foreach($userRepo->findAll() as $user) {
            $storedDescriptor = $user->getFaceDescriptor();
            $distance = $this->euclideanDistance($descriptor, $storedDescriptor);
            if($distance < 0.6) {
                $this->get('security.token_storage')->setToken(
                    new UsernamePasswordToken($user, null, 'main', $user->getRoles())
                );
                return new JsonResponse(['success' => true]);
            }
        }

        return new JsonResponse(['success' => false, 'message' => 'Aucun visage correspondant trouvé.']);
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        for($i = 0; $i < count($a); $i++) {
            $sum += ($a[$i] - $b[$i]) ** 2;
        }
        return sqrt($sum);
    }
}
