# Curso: Introducción a la accesibilidad web
Aprende los principios básicos para crear sitios web que todas las personas puedan usar,
independientemente de sus capacidades o de la tecnología con la que naveguen.

**Duración estimada:** 4 horas.

## Módulo: Fundamentos
Qué es la accesibilidad, a quién beneficia y qué dice la normativa.

### Unidad: ¿Qué es la accesibilidad web?
La **accesibilidad web** consiste en diseñar y desarrollar sitios y aplicaciones para que
cualquier persona pueda percibirlos, entenderlos, navegar por ellos e interactuar con ellos.

Beneficia especialmente a personas con:

- discapacidad visual (ceguera, baja visión, daltonismo);
- discapacidad auditiva;
- discapacidad motriz;
- discapacidad cognitiva o del aprendizaje.

Pero también ayuda a personas mayores, a quien usa el móvil a pleno sol o a quien tiene
una conexión lenta.

#### Las pautas WCAG
Las *Web Content Accessibility Guidelines* (WCAG) del W3C se organizan en cuatro principios:

| Principio    | Significado                                              |
|--------------|----------------------------------------------------------|
| Perceptible  | La información se puede percibir por algún sentido.      |
| Operable     | La interfaz se puede manejar, por ejemplo, con teclado.  |
| Comprensible | El contenido y el funcionamiento se entienden.           |
| Robusto      | Funciona con distintas tecnologías, incluidas las de apoyo. |

### Unidad: Normativa en España y en la Unión Europea
En España, el **Real Decreto 1112/2018** obliga a los sitios web del sector público a cumplir
la norma EN 301 549, que se basa en las WCAG 2.1 de nivel AA.

Además, la **Ley Europea de Accesibilidad** (Directiva 2019/882) extiende requisitos
de accesibilidad a numerosos productos y servicios del sector privado desde junio de 2025.

### Ejercicio: Identifica barreras
Piensa en un sitio web que uses con frecuencia.

1. Describe **dos barreras de accesibilidad** que podría encontrar una persona usuaria de lector de pantalla.
2. Indica a qué principio de las WCAG afecta cada una.
3. Propón una solución para cada barrera.

## Módulo: Buenas prácticas
Técnicas concretas que puedes aplicar desde hoy.

### Unidad: Textos alternativos
Toda imagen que transmita información necesita un texto alternativo que la describa:

```html
<img src="grafico-ventas.png" alt="Las ventas crecieron un 20 % entre enero y marzo">
```

Si la imagen es decorativa, usa un `alt` vacío (`alt=""`) para que los lectores de pantalla la ignoren.

### Unidad: Navegación con teclado
Todo lo que se puede hacer con el ratón debe poder hacerse con el teclado:

- el orden de tabulación debe seguir el orden lógico de la página;
- el foco debe ser **siempre visible**;
- no debe haber trampas de teclado;
- los enlaces para «saltar al contenido» ayudan a evitar bloques repetidos.

### Ejercicio: Revisa un formulario
Recorre con el teclado (sin usar el ratón) el formulario de registro de cualquier sitio web.

Explica qué problemas has encontrado relacionados con las **etiquetas de los campos**,
el **orden del foco** y la **forma de mostrar los errores**, y cómo los corregirías.
