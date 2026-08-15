# Guía de usuario de PlayBrick

Esta guía está dirigida a diseñadores y desarrolladores que usan Bricks Builder, PlayBrick y Tailwind CSS. El recorrido rápido añade una utilidad de Tailwind a un elemento seleccionado; las secciones posteriores explican el alcance, los tokens, los breakpoints, los archivos generados y los pasos de recuperación.

## Inicio rápido

1. Abre **Settings → PlayBrick** y confirma que **Playground path** apunta al scaffold que vas a usar.
2. Si el scaffold no existe, activa PlayBrick para que cree `wp-content/playground/`.
3. Abre una terminal en el playground:

   ```bash
   pnpm install
   pnpm run watch
   ```

4. Mantén el watcher ejecutándose y abre la página en Bricks Builder.
5. Selecciona un elemento, abre el panel CSS de PlayBrick e introduce utilidades como `grid gap-5 md:grid-cols-2` en el campo de utilidades.
6. Guarda el elemento en Bricks y confirma el CSS generado y la vista previa del frontend.

El watcher exporta las fuentes de Bricks y reconstruye `dev.built.css` cuando cambian las fuentes relevantes o los archivos del scaffold. Usa `pnpm run build` al preparar los assets de producción.

## Elige el nivel de estilo

Usa el alcance más pequeño que coincida con la intención del diseño.

| Nivel | Úsalo para | Se almacena en Bricks | Ejemplo |
|-------|------------|------------------|---------|
| **Utilidades directas de Tailwind** | Una composición puntual o específica de un elemento | El `_cssClasses` del elemento activo, expuesto por el campo de utilidades de PlayBrick | `flex items-center gap-3 md:gap-5` |
| **Custom CSS específico del elemento** | Una regla con alcance de selector que no resulta práctica como utilidades | El `_cssCustom` del elemento activo, exportado contra `#brxe-{id}` para elementos normales | `@apply grid gap-5;` |
| **Clase global reutilizable de Bricks** | Un estilo de componente compartido por varios elementos | Una clase global de Bricks y su `_cssCustom` o sus controles visuales | `.card { @apply rounded-card shadow-card; }` |

No crees una clase global solo para contener las utilidades de cada elemento. Usa una clase global cuando el estilo tenga un rol semántico reutilizable.

## Uso del panel CSS

### Modos de destino

El panel sigue la selección activa de Bricks:

- **Destino de clase global:** edita el Custom CSS de la clase seleccionada e inspecciona sus controles visuales compatibles.
- **Destino de elemento activo:** edita el `_cssClasses` y el `_cssCustom` con alcance de elemento del elemento seleccionado sin crear primero una clase global.

Si no hay una clase global o un elemento activo válido seleccionado, el panel muestra un mensaje de estado vacío. Cambia de elemento y usa **Refresh** si la selección del builder cambió pero el panel no se actualizó.

### CSS generado y Custom CSS

- El **CSS generado** es de solo lectura. Se traduce desde ajustes visuales compatibles de Bricks, como layout, espaciado, tipografía, fondos, bordes, radius y sombras.
- El **Custom CSS** es editable y se almacena en `_cssCustom` de Bricks para el destino actual y el breakpoint activo.
- El campo de utilidades es `_cssClasses` directo en el modo de elemento. En el modo de clase global está pensado para contenido `@apply` de Tailwind en el Custom CSS de la clase.
- Usa **Copy generated** o **Copy declarations** cuando necesites inspeccionar o reutilizar la salida del panel.

### Apply to visual

**Apply to visual** analiza las declaraciones compatibles del Custom CSS y las mueve a los controles visuales de Bricks. Las declaraciones no compatibles permanecen en Custom CSS. Esto mantiene separado el CSS de los controles visuales del Custom CSS; no dupliques la misma declaración en ambos lugares.

### Clear

**Clear custom** limpia el Custom CSS del breakpoint del destino actual. Para un elemento activo también limpia las utilidades directas de Tailwind almacenadas en `_cssClasses`. No elimina una clase global ni quita ajustes visuales de Bricks que no estén relacionados. Confirma el destino y el breakpoint antes de limpiar.

### Breakpoints y controles directos de Bricks

El panel lee el breakpoint activo de Bricks. El Custom CSS base se almacena en `_cssCustom`; el CSS específico de un breakpoint usa `_cssCustom:<breakpoint>`. Los controles visuales directos de Bricks siguen siendo ajustes nativos de Bricks y pueden sobrescribir o competir con el Custom CSS según la especificidad del selector y el orden de renderizado.

## Tokens de Bricks en Tailwind

PlayBrick exporta las variables del Style Manager de Bricks y la paleta de colores a `.playbrick/bricks-theme.css` como tokens `@theme inline` de Tailwind. Bricks sigue siendo la fuente de verdad: cambia el valor en **Bricks → Settings → Style Manager** y deja que el watcher reconstruya.

### Convención de nombres

Usa los nombres compatibles siguientes al crear variables de Bricks:

| Nombre de variable de Bricks | Namespace del token de Tailwind | Utilidades habituales |
|----------------------|--------------------------|--------------------|
| `space-m` | `--spacing-m` respaldado por `--space-m` | `p-m`, `gap-m`, `mt-m` |
| `text-xl` | `--text-xl` | `text-xl` |
| `color-primary` | `--color-primary` | `bg-primary`, `text-primary`, `border-primary` |
| `radius-card` o `card-radius` | `--radius-card` | `rounded-card` |
| `shadow-card` | `--shadow-card` | `shadow-card` |
| `leading-normal` | `--leading-normal` | `leading-normal` |
| `font-title` | `--font-title` | `font-title` |

Por ejemplo, una variable de Bricks llamada `space-m` puede usarse como `gap-m`, y un color de paleta llamado `Brand Amber` se convierte en un slug como `bg-brand-amber`. Los nombres de tokens duplicados reciben sufijos numéricos en lugar de reemplazar silenciosamente otro valor.

### Las categorías de variables no definen el namespace de Tailwind

Una categoría de variables de Bricks solo sirve para organizar visualmente los datos. Llamar a una categoría `Colors` o `eqColors` no convierte sus variables en tokens de color de Tailwind. Para que una variable global se exporte al namespace de colores de Tailwind, usa el prefijo `color-*`:

```text
color-verde-oscuro
color-verde-menta
color-verde-azulado
```

Esto produce tokens como `--color-verde-oscuro` y utilidades como `bg-verde-oscuro`, `text-verde-oscuro` y `border-verde-oscuro`. Los colores creados en la paleta de colores de Bricks se exportan como colores independientemente de esta convención de nombres. Las variables importadas que originalmente estaban organizadas como colores pueden llegar como variables globales comunes, así que verificá sus nombres después de una migración.

### Limitación de los tokens de contenedor

Los nombres de contenedor arbitrarios como `deskbox` no se asignan actualmente a un namespace de contenedores de Tailwind. Las opciones seguras son:

```css
max-width: var(--deskbox);
```

o un valor arbitrario de Tailwind cuando sea compatible:

```html
<div class="max-w-[var(--deskbox)]"></div>
```

No edites `.playbrick/bricks-theme.css` para añadir un token faltante; es generado y se sobrescribirá.

## Estilos responsive

Prefiere las variantes de Tailwind en las clases de utilidades directas:

```text
text-base md:text-lg max-md:text-sm
```

Usa la utilidad base para el estado predeterminado y añade una variante solo cuando cambie el valor. Evita colocar accidentalmente una utilidad exclusiva para móvil en la lista de clases base del elemento.

Al usar Custom CSS de Bricks, PlayBrick trata `_cssCustom:<breakpoint>` como datos con alcance de breakpoint y los envuelve con la dirección de media query activa de Bricks. Las claves de breakpoint desconocidas se omiten en lugar de emitirse globalmente. No edites manualmente el CSS generado ni añadas tu propio wrapper a un archivo generado.

## Flujo de build y producción

### Desarrollo

1. Confirma que **Settings → PlayBrick → Environment** sea `dev`.
2. Ejecuta `pnpm install` una vez en el playground.
3. Ejecuta `pnpm run watch` mientras editas archivos de Bricks o del playground.
4. Usa el builder de Bricks y la vista previa del navegador para comprobar el resultado.

### Producción

1. Detén el watcher o déjalo aparte y ejecuta `pnpm run build` desde el playground.
2. En **Settings → PlayBrick**, cambia **Environment** a `prod`/`Production`, tal como indique la instalación.
3. Confirma que la estrategia de enqueue configurada cargue los assets minificados generados.

El flujo de Tailwind escribe estos archivos generados en el playground:

| Archivo | Propósito |
|------|---------|
| `.playbrick/bricks-sources.html` | Clases de Bricks escapadas para el escaneo de Tailwind |
| `.playbrick/bricks-sources.txt` | Clases de Bricks sin procesar para el escaneo de Tailwind |
| `.playbrick/bricks-custom.css` | Custom CSS global y de elementos de Bricks procesado por Tailwind |
| `.playbrick/bricks-theme.css` | Variables y paleta de Bricks como tokens `@theme inline` |
| `.playbrick/tailwind-utilities.json` | Sugerencias de utilidades del proyecto para el panel |
| `dev.built.css` | Salida CSS del modo watch |
| `playbrick.reload.json` | Marca de tiempo de recarga del modo watch |

Estos archivos son generados. No los edites manualmente.

## Resolución de problemas

| Problema | Causa probable | Solución |
|---------|--------------|----------|
| La utilidad no se genera | La clase no está en las fuentes escaneadas, el watcher está detenido o Tailwind rechazó la utilidad | Mantén `pnpm run watch` ejecutándose, guarda el elemento de Bricks, inspecciona `.playbrick/bricks-sources.txt` y ejecuta `pnpm run build` para ver el error de build. |
| La variable no se reconoce | El nombre de la variable de Bricks está fuera de los patrones de tokens compatibles o la exportación del tema está obsoleta | Renómbrala usando `space-*`, `text-*`, `color-*`, `radius-*`, `shadow-*`, `leading-*` o `font-*`; reconstruye; usa `var(--name)` para tokens arbitrarios. |
| El estilo móvil se filtra al escritorio | Se colocó un valor móvil en la utilidad/CSS base o Custom CSS usó una clave de breakpoint desconocida | Coloca el valor en una variante de Tailwind o en el campo `_cssCustom:<breakpoint>` correcto; nunca edites el CSS generado. |
| El tamaño de fuente y la altura de línea colisionan | Los controles de tipografía de Bricks y las utilidades `text-*` de Tailwind establecen valores relacionados, o gana un selector posterior | Inspecciona el CSS generado y Rules del navegador; conserva el tamaño de fuente/altura de línea previsto en un solo nivel de estilo y elimina la declaración duplicada. |
| Las clases no son visibles después de cambiar de elemento | El panel no se sincronizó con el nuevo elemento activo | Selecciona de nuevo el elemento y pulsa **Refresh**. Confirma que las clases estén en `_cssClasses` del elemento, no solo en el CSS generado. |
| Clear no eliminó lo esperado | Clear tiene alcance sobre el destino y breakpoint actuales; los controles visuales no se limpian | Selecciona el destino/breakpoint correctos. Clear Custom elimina las utilidades del elemento en el modo de elemento, pero los ajustes visuales directos de Bricks deben cambiarse en Bricks. |
| Bricks sobrescribe el CSS | Gana un ajuste visual de Bricks, una clase global, un estilo inline o un selector más específico | Usa Rules de DevTools del navegador y elimina la declaración en conflicto o elige el nivel de estilo correcto. Evita apilar `!important` como primera solución. |
| El CSS o los tokens están obsoletos | El watcher no detectó un cambio de fuente, falló una exportación o la caché del navegador/WordPress sirve assets antiguos | Guarda el cambio del builder, pulsa **Refresh** en el panel, reinicia `pnpm run watch`, ejecuta `pnpm run build` y purga la caché correspondiente. Comprueba que cambie la marca de tiempo de `dev.built.css`. |
| El contexto del selector de un componente se comporta de forma inesperada | Una definición de componente y un elemento independiente no comparten el mismo contexto de selector | Trata el CSS del componente como CSS de una definición reutilizable; inspecciona el selector renderizado en DevTools y no supongas que `#brxe-{id}` es válido para todas las instancias de componentes. |

## Lista de comprobación del flujo final

- [ ] El Style Manager de Bricks contiene el valor canónico del token.
- [ ] El nivel de estilo coincide con el alcance: utilidad directa, CSS del elemento o clase global reutilizable.
- [ ] El destino y breakpoint activos están visibles antes de editar o limpiar.
- [ ] Los cambios responsive usan variantes de Tailwind o una clave de breakpoint válida de Bricks.
- [ ] `pnpm run watch` está ejecutándose durante el desarrollo.
- [ ] Los archivos `.playbrick` generados no se editaron manualmente.
- [ ] El CSS generado, la vista previa visual de Bricks y el resultado en el navegador frontend coinciden.
- [ ] `pnpm run build` y la estrategia de enqueue configurada están verificados antes de producción.
