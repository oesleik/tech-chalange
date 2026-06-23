<?php

declare(strict_types=1);

namespace Symfony\Component\Translation;

class IdentityTranslator implements \Symfony\Contracts\Translation\TranslatorInterface {
	public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string {
		return "";
	}

	public function getLocale(): string {
		return "";
	}
}
