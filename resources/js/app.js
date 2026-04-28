import './bootstrap';
import * as Turbo from '@hotwired/turbo';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Mostrar barra de progreso desde el primer instante en conexiones lentas
Turbo.config.drive.progressBarDelay = 0;

Alpine.start();
