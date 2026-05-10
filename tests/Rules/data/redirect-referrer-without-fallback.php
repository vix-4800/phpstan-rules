<?php

declare(strict_types=1);

namespace Fixtures {
    final class Controller
    {
        public function redirect(mixed $url): void
        {
        }

        public function unsafeFromRequest(object $request): void
        {
            $this->redirect($request->referrer);
        }

        public function unsafeFromVariable(string $referrer): void
        {
            $this->redirect($referrer);
        }

        public function safeFallback(object $request): void
        {
            $this->redirect($request->referrer ?: ['index']);
        }

        public function safeVariableFallback(?string $referrer): void
        {
            $this->redirect($referrer ?? ['index']);
        }
    }
}
