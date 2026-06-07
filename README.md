# TodoCamisetas API 🧢

API RESTful en **Laravel 13 + PHP 8.3 + MySQL 8** para gestión de inventario B2B de TodoCamisetas.

## Levantar el proyecto

```bash
# 1. Clonar
git clone https://github.com/ignacioleiva66-ai/backend.git
cd backend

# 2. Variables de entorno
cp .env.example .env

# 3. Docker
docker-compose up -d

# 4. Instalar dependencias
docker exec todocamisetas_app composer install

# 5. Key de Laravel
docker exec todocamisetas_app php artisan key:generate

# 6. Tablas + datos iniciales
docker exec todocamisetas_app php artisan migrate --seed
```

**Verificar:** http://localhost:8000/api/clientes → debe devolver JSON con 90minutos y tdeportes.

**phpMyAdmin:** http://localhost:8080

---

## Endpoints disponibles

| Recurso | Rutas |
|---------|-------|
| Camisetas | `GET/POST /api/camisetas` · `GET/PUT/PATCH/DELETE /api/camisetas/{id}` |
| Precio final | `GET /api/camisetas/{id}/precio?cliente_id=1` |
| Clientes | `GET/POST /api/clientes` · `GET/PUT/PATCH/DELETE /api/clientes/{id}` |
| Contactos Empresa | `GET/POST /api/clientes/{id}/contactos-empresa` |
| Contactos Personal | `GET/POST /api/clientes/{id}/contactos-personal` |
| Tallas | `GET/POST /api/tallas` · `GET/PUT/PATCH/DELETE /api/tallas/{id}` |
| Ventas | `GET/POST /api/ventas` · `GET /api/ventas/estadisticas` |

---

## Documentación

- **Swagger:** Abrir `docs/swagger.yaml` en https://editor.swagger.io
- **Postman:** Importar `docs/postman_collection.json`

---

## Lógica de precios

- Cliente **Preferencial** + `precio_oferta` → usa `precio_oferta`
- Cliente con `porcentaje_oferta > 0` → descuento porcentual
- Ambas → el **menor** precio
- Ninguna → precio base

## Validación RUT

Algoritmo módulo 11 (SII Chile) en `app/Helpers/RutValidator.php`.
