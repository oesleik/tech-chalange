<?php

declare(strict_types=1);

namespace App\Core\Contract;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ContractResolver {

	public function __construct(
		private ValidatorInterface $validator,
		private DenormalizerInterface $denormalizer,
		private NormalizerInterface $normalizer,
		private SerializerInterface $serializer,
	) {
	}

	/**
	 * @template T of AbstractContract
	 * @param class-string<T> $contractClass
	 * @return T
	 */
	public function fromJson(string $payload, string $contractClass): object {
		return $this->fromArray(json_decode($payload, true), $contractClass);
	}

	/**
	 * @template T of AbstractContract
	 * @param class-string<T> $contractClass
	 * @return T
	 */
	public function fromArray(array $payload, string $contractClass): object {
		$violations = $this->validator->validate($payload, $contractClass::getConstraints());

		if (count($violations)) {
			throw new InvalidContractException($violations);
		}

		return $this->denormalizer->denormalize($payload, $contractClass);
	}

	public function toArray(object $data): array {
		return $this->normalizer->normalize($data, "json");
	}

	public function toJson(object $data): string {
		return $this->serializer->serialize($data, "json");
	}

}
