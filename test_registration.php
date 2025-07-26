<?php
// Quick test script to validate form constraints
// Run with: php test_registration.php

require_once 'vendor/autoload.php';

use App\Entity\User;
use App\Form\RegistrationForm;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// Create form factory
$formFactory = Forms::createFormFactory();

// Create validator
$validator = Validation::createValidatorBuilder()
    ->enableAttributeMapping()
    ->getValidator();

// Test data
$testData = [
    'pseudo' => 'testuser',
    'email' => 'test@example.com',
    'plainPassword' => 'password123',
    'agreeTerms' => true
];

$user = new User();
$form = $formFactory->create(RegistrationForm::class, $user);

// Submit test data
$form->submit($testData);

echo "Form submitted: " . ($form->isSubmitted() ? 'YES' : 'NO') . "\n";
echo "Form valid: " . ($form->isValid() ? 'YES' : 'NO') . "\n";

if (!$form->isValid()) {
    echo "Form errors:\n";
    foreach ($form->getErrors(true) as $error) {
        echo "- " . $error->getMessage() . "\n";
    }
}

// Validate user entity
$user->setPseudo($testData['pseudo']);
$user->setEmail($testData['email']);

$violations = $validator->validate($user);

echo "\nEntity violations: " . count($violations) . "\n";
foreach ($violations as $violation) {
    echo "- " . $violation->getMessage() . " (property: " . $violation->getPropertyPath() . ")\n";
}

echo "\nTest completed.\n";
