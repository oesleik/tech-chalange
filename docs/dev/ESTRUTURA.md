# Estrutura e checklist

- [README.md](./README.md)
- [EXEMPLOS.md](./EXEMPLOS.md)

## Pastas

```
src/{Modulo}/
├── Domain/
│   ├── Entity/{Recurso}.php
│   ├── ValueObject/{Vo}.php
│   └── Exception/{Recurso}*Exception.php
├── Application/
│   ├── Gateway/{Recurso}GatewayInterface.php
│   └── UseCase/{Acao}{Recurso}/
│       ├── {Acao}{Recurso}UseCase.php
│       ├── {Acao}{Recurso}InputDTO.php      # quando há entrada
│       └── {Acao}{Recurso}OutputDTO.php     # quando saída não é só Entity
├── Infrastructure/Persistence/
│   ├── {Recurso}Gateway.php
│   └── {Recurso}Mapper.php
└── Presentation/Http/
    ├── Router/{Acao}{Recurso}Router.php
    ├── Controller/{Acao}{Recurso}Controller.php
    └── DTO/
        ├── {Acao}{Recurso}Mapper.php        # HTTP -> Input DTO
        └── {Recurso}ResponseDTO.php
```

Referências fora do módulo:

- `configs/service-container/core.definitions.php`: `GatewayInterface` -> `Gateway`
- `router.php`: rota -> `*Router`

## Checklist

- [ ] Domain: Entity + ValueObject + Exceptions
- [ ] Application: interfaces + use cases + DTOs
- [ ] Infrastructure: Gateway + Mapper
- [ ] Presentation: Mapper + ResponseDTO + Controller + Router
- [ ] DI do Gateway + rotas
- [ ] Teste de use case mockando a interface
- [ ] Sem Model/Service/Validator; sem HTTP na Application;
