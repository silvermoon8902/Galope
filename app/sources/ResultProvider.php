<?php
/**
 * Punto de extension para la fuente de resultados de carreras.
 *
 * Hoy los resultados se cargan a mano desde el panel de administracion. Cuando
 * se defina una API de carreras de caballos, se crea una clase que implemente
 * esta interfaz, consulte esa API y llame a:
 *
 *     RaceService::loadResult($raceId, $first, $second, $third, null, 'api');
 *
 * El motor de puntuacion no cambia: solo cambia de donde llega el resultado.
 * Un proceso programado (cron) puede recorrer las carreras bloqueadas sin
 * resultado y completarlas con el proveedor configurado.
 */
interface ResultProvider
{
    /**
     * Devuelve el podio oficial de una carrera como [id_1ro, id_2do, id_3ro]
     * usando los ids de la tabla horses, o null si el resultado todavia no
     * esta disponible en la fuente.
     */
    public function fetchPodium(int $raceId): ?array;
}
