# Hoja de ruta de PlayBrick

PlayBrick conecta los controles visuales y los tokens de diseño de Bricks Builder con un flujo de desarrollo basado en Tailwind CSS v4. La hoja de ruta mantiene a Bricks como fuente visual de verdad, a la vez que hace que la creación de utilidades, la inspección de CSS y la entrega a producción sean más seguras y rápidas.

## Arquitectura orientadora

- Bricks administra los valores visuales, las variables globales, las paletas de colores, las definiciones de breakpoints y las clases globales reutilizables.
- PlayBrick exporta el contenido y los tokens de Bricks a archivos fuente `.playbrick` consumidos por Tailwind.
- Tailwind genera el paquete CSS; PlayBrick no crea una clase global para cada elemento.
- El panel CSS es una ayuda de edición. Debe exponer el destino correcto de Bricks sin reemplazar sus controles visuales nativos.
- Cada mejora debe conservar un recorrido claro desde un cambio en el builder hasta el código fuente generado, el CSS generado y la página renderizada.

## Línea base actual entregada

La implementación actual proporciona:

- Un scaffold de Tailwind con los flujos de trabajo `pnpm install`, `pnpm run watch` y `pnpm run build`.
- Escaneo de clases y contenido de Bricks hacia `.playbrick/bricks-sources.html` y `.playbrick/bricks-sources.txt`.
- Exportación del Custom CSS de las clases globales y del Custom CSS de los elementos de Bricks hacia `.playbrick/bricks-custom.css`.
- Integración de las variables del Style Manager y los colores de la paleta de Bricks en `.playbrick/bricks-theme.css` mediante tokens `@theme inline` de Tailwind.
- Un panel CSS del builder con dos modos de destino: la clase global activa y el elemento de Bricks activo.
- Edición directa de utilidades de elementos mediante `_cssClasses`, junto con edición de `_cssCustom` con alcance de elemento.
- CSS de solo lectura generado a partir de controles visuales compatibles de Bricks y una acción `Apply to visual` para declaraciones compatibles.
- Sugerencias integradas de CSS y utilidades de Tailwind, incluidas las utilidades del proyecto de `.playbrick/tailwind-utilities.json`.
- Exportación adaptada a breakpoints para `_cssCustom:<breakpoint>`, omitiendo las claves de breakpoint no resueltas en lugar de filtrarlas globalmente.
- Exportación de fuentes en modo watch, reconstrucciones de Tailwind, `dev.built.css` generado y señal de recarga mediante `playbrick.reload.json`.
- Cobertura PHPUnit para la extracción de fuentes, el mapeo de tokens, el envoltorio por breakpoint y los datos de autocompletado.

## Hoja de ruta priorizada

### Ahora: creación más segura y feedback más claro

| Elemento | Valor para el usuario | Dirección de implementación | Señal de aceptación |
|------|------------|--------------------------|-------------------|
| **P0: Autocompletado de utilidades y ordenamiento de clases** | Encontrar rápidamente utilidades válidas del proyecto y mantener las cadenas de clases legibles y estables. | Ampliar los metadatos de autocompletado con namespaces, variantes y origen del token; ordenar las utilidades por variante, namespace y nombre de utilidad sin cambiar el orden semántico cuando importe el orden de conflictos de Tailwind. | Las sugerencias distinguen las utilidades integradas de las del proyecto; las ediciones repetidas producen un orden determinista de clases; las variantes responsive siguen siendo válidas. |
| **P0: Diagnóstico de la cascada** | Explicar por qué un estilo no aparece en lugar de pedir a los usuarios que adivinen. | Comparar las declaraciones generadas, el Custom CSS del elemento, las clases globales, los ajustes visuales de Bricks y la evidencia de estilos calculados cuando esté disponible. Informar los posibles ganadores y la especificidad. | Un diagnóstico del panel identifica la fuente ganadora en conflictos comunes como tamaño de fuente, altura de línea y sobrescrituras de Bricks. |
| **P0: Reglas de caché e invalidación** | Evitar CSS obsoleto después de un cambio en el builder o en un token. | Definir fingerprints para las fuentes de Bricks, los tokens del tema, el Custom CSS y la configuración; invalidar solo las exportaciones afectadas y mostrar el motivo y la hora de la última exportación. | Una edición de token o clase reconstruye una sola vez; las entradas sin cambios no se reconstruyen; el panel puede informar datos generados obsoletos. |
| **P0: Seguridad de breakpoints responsive** | Garantizar que las ediciones móviles nunca se filtren a los estilos de escritorio. | Leer el mapa de breakpoints activo de Bricks, validar la dirección de las variantes, conservar las reglas base y mostrar las claves de breakpoint no resueltas o en conflicto antes de exportar. | Las pruebas cubren mapas desktop-first y mobile-first; una clave inválida no puede emitir CSS sin alcance; la verificación en el navegador confirma el aislamiento entre escritorio y móvil. |

### Siguiente: integración coherente con el sistema de diseño

| Elemento | Valor para el usuario | Dirección de implementación | Señal de aceptación |
|------|------------|--------------------------|-------------------|
| **P1: Sincronización de tokens de diseño** | Hacer predecibles los tokens de Bricks renombrados o modificados en Tailwind. | Añadir un manifiesto de exportación explícito que contenga la variable de origen, el token de Tailwind generado, el fallback, el sufijo de colisión y el motivo de incompatibilidad. Mantener la sincronización unidireccional desde Bricks, salvo que se configure intencionalmente otra cosa. | Los usuarios pueden rastrear `space-m` hasta `--spacing-m` y ver colisiones o nombres no compatibles sin inspeccionar manualmente los archivos generados. |
| **P1: Contexto de componentes** | Aplicar estilos a definiciones e instancias de componentes sin colisiones de selectores. | Modelar por separado los elementos independientes, las definiciones de componentes y las instancias de componentes; usar selectores conscientes del contexto y evitar que una edición de instancia cambie silenciosamente la definición. | El CSS de componentes usa el selector correcto para su contexto y una prueba de regresión demuestra que los estilos de instancias y elementos independientes no colisionan. |
| **P1: Contrato de CSS de producción** | Publicar solo el CSS y JavaScript que necesita producción. | Documentar y validar el contrato del artefacto de build, el directorio de salida, la estrategia de enqueue, la política de source maps/debug y la eliminación del comportamiento de recarga de desarrollo. | `pnpm run build` produce assets minificados reproducibles, producción no depende de archivos de recarga `.playbrick` y la ruta de enqueue configurada carga una sola copia. |
| **P1: Pruebas y verificación en el navegador** | Detectar regresiones silenciosas del builder antes de publicar. | Mantener pruebas PHP enfocadas en extracción y mapeo de tokens; añadir pruebas con fixtures para el estado del panel; añadir comprobaciones de navegador para cambio de destino, `Apply to visual`, Clear, comportamiento responsive y recuperación de caché obsoleta. | CI supera las pruebas unitarias y un flujo smoke repetible del navegador verifica el panel en una página representativa de Bricks. |

### Más adelante: una experiencia diaria más fluida

| Elemento | Valor para el usuario | Dirección de implementación | Señal de aceptación |
|------|------------|--------------------------|-------------------|
| **P2: Mejoras de UX del panel** | Reducir la fricción al cambiar de elemento y editar CSS. | Conservar el estado del destino de forma fiable, mostrar de manera destacada el elemento/clase y el breakpoint activos, añadir mensajes de estado enfocados, mejorar la navegación por teclado y hacer explícito el comportamiento de Clear. | Un usuario puede cambiar de destino, volver a un elemento, entender sus clases y limpiar el alcance previsto sin actualizar la página. |
| **P2: Recursos de creación inspirados en WindPress** | Ofrecer una experiencia pulida de creación utility-first. | Tomar ideas de producto como la entrada rápida de utilidades, las sugerencias contextuales y los tokens visibles del proyecto, pero conservar el pipeline de exportación de PlayBrick y el modelo de almacenamiento nativo de Bricks. | La experiencia es más rápida de aprender sin vincular utilidades a IDs de Bricks generados ni introducir una segunda autoridad de estilos. |
| **P2: Vista previa explicable de fuentes** | Permitir que los usuarios entiendan qué está escaneando Tailwind y por qué existe una utilidad. | Vincular las sugerencias del panel con su categoría de origen y mostrar un resumen compacto de exportación en lugar de exigir que los usuarios abran los archivos generados. | Un usuario puede identificar si una utilidad proviene de autocompletados del core, contenido de Bricks, un token o Custom CSS. |

## Guardrails y objetivos excluidos

- No crear una clase global para cada elemento. Usar `_cssClasses` directo para la composición local de utilidades y clases globales para estilos de componentes realmente reutilizables.
- No vincular utilidades de Tailwind a IDs de Bricks como reemplazo de clases semánticas. Los IDs de elementos son contexto de selector para CSS con alcance, no nombres del sistema de diseño.
- Mantener a Bricks como fuente visual de verdad de los tokens, salvo que una funcionalidad futura cambie intencionalmente ese contrato y documente el comportamiento de migración.
- No editar manualmente los archivos generados en el directorio de salida CSS de WordPress Bricks ni `.playbrick`.
- No duplicar el CSS de los controles visuales en Custom CSS solo para que el panel parezca completo.
- No copiar la arquitectura de WindPress. PlayBrick debe conservar su pequeño límite de exportación, sus archivos generados explícitos y la persistencia nativa de Bricks.
- No prometer namespaces arbitrarios de tokens de Tailwind hasta que su mapeo y comportamiento ante colisiones estén implementados y probados.

## Lista de comprobación para decidir una publicación

- [ ] La funcionalidad tiene un resultado visible para el usuario y una señal de aceptación medible.
- [ ] La fuente de verdad de Bricks y el alcance del selector están explícitos.
- [ ] El comportamiento base y responsive está cubierto por pruebas.
- [ ] Los artefactos generados y el comportamiento de invalidación están documentados.
- [ ] Una comprobación smoke del navegador confirma el resultado en el builder y en el frontend.
