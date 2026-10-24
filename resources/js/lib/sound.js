/**
 * Web Audio API synthesizer for instant audio cues without external assets.
 */
class SoundEffects {
    constructor() {
        this.ctx = null;
    }

    init() {
        if (!this.ctx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.ctx = new AudioContext();
            }
        }
        if (this.ctx && this.ctx.state === 'suspended') {
            this.ctx.resume();
        }
    }

    playTone(frequency, type, duration, delay = 0, gainLevel = 0.15) {
        try {
            this.init();
            if (!this.ctx) return;

            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();

            osc.type = type;
            osc.frequency.setValueAtTime(frequency, this.ctx.currentTime + delay);

            gain.gain.setValueAtTime(gainLevel, this.ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, this.ctx.currentTime + delay + duration);

            osc.connect(gain);
            gain.connect(this.ctx.destination);

            osc.start(this.ctx.currentTime + delay);
            osc.stop(this.ctx.currentTime + delay + duration);
        } catch (e) {
            console.warn('Audio playback error', e);
        }
    }

    // Success check-in: ascending pleasant major triad
    playSuccess() {
        this.playTone(523.25, 'sine', 0.12, 0, 0.2);       // C5
        this.playTone(659.25, 'sine', 0.15, 0.08, 0.2);    // E5
        this.playTone(783.99, 'sine', 0.25, 0.16, 0.25);   // G5
    }

    // Success check-out: pleasant descending chord
    playCheckout() {
        this.playTone(783.99, 'sine', 0.12, 0, 0.2);
        this.playTone(659.25, 'sine', 0.15, 0.08, 0.2);
        this.playTone(523.25, 'sine', 0.25, 0.16, 0.25);
    }

    // Warning (e.g. late check-in or duplicate tap)
    playWarning() {
        this.playTone(440, 'triangle', 0.12, 0, 0.2);
        this.playTone(392, 'triangle', 0.18, 0.14, 0.2);
    }

    // Error (unregistered card / rejected)
    playError() {
        this.playTone(220, 'sawtooth', 0.15, 0, 0.2);
        this.playTone(196, 'sawtooth', 0.25, 0.12, 0.25);
    }
}

export const sounds = new SoundEffects();

