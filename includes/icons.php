<?php
// Kino Coin Laundry - Vector SVG Line Icons
// Matches 100% Figma Page 2 Outlined Icons

function get_icon($name, $size = 20, $color = 'currentColor') {
    $common = "width=\"{$size}\" height=\"{$size}\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"{$color}\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"svg-icon svg-icon-{$name}\"";
    
    switch ($name) {
        case 'home':
            return "<svg {$common}>
                <path d=\"M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z\"></path>
                <polyline points=\"9 22 9 12 15 12 15 22\"></polyline
            </svg>";
        
        case 'transaksi':
        case 'list':
            return "<svg {$common}>
                <line x1=\"8\" y1=\"6\" x2=\"21\" y2=\"6\"></line>
                <line x1=\"8\" y1=\"12\" x2=\"21\" y2=\"12\"></line>
                <line x1=\"8\" y1=\"18\" x2=\"21\" y2=\"18\"></line>
                <circle cx=\"3.5\" cy=\"6\" r=\"1\" fill=\"currentColor\"></circle>
                <circle cx=\"3.5\" cy=\"12\" r=\"1\" fill=\"currentColor\"></circle>
                <circle cx=\"3.5\" cy=\"18\" r=\"1\" fill=\"currentColor\"></circle
            </svg>";

        case 'pelanggan':
        case 'user':
        case 'users':
            return "<svg {$common}>
                <path d=\"M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2\"></path>
                <circle cx=\"9\" cy=\"7\" r=\"4\"></circle>
                <path d=\"M23 21v-2a4 4 0 0 0-3-3.87\"></path>
                <path d=\"M16 3.13a4 4 0 0 1 0 7.75\"></path
            </svg>";

        case 'rekap':
        case 'chart':
        case 'laporan':
            return "<svg {$common}>
                <line x1=\"18\" y1=\"20\" x2=\"18\" y2=\"10\"></line>
                <line x1=\"12\" y1=\"20\" x2=\"12\" y2=\"4\"></line>
                <line x1=\"6\" y1=\"20\" x2=\"6\" y2=\"14\"></line
            </svg>";

        case 'keluar':
        case 'logout':
            return "<svg {$common}>
                <path d=\"M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4\"></path>
                <polyline points=\"16 17 21 12 16 7\"></polyline>
                <line x1=\"21\" y1=\"12\" x2=\"9\" y2=\"12\"></line
            </svg>";

        case 'master':
        case 'basket':
            return "<svg {$common}>
                <path d=\"M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z\"></path>
                <line x1=\"3\" y1=\"6\" x2=\"21\" y2=\"6\"></line>
                <path d=\"M16 10a4 4 0 0 1-8 0\"></path
            </svg>";

        case 'presensi':
        case 'fingerprint':
            return "<svg {$common}>
                <path d=\"M2 12C2 6.5 6.5 2 12 2a10 10 0 0 1 8 4\"></path>
                <path d=\"M5 19.5C5.5 18 6 15 6 12c0-.7.1-1.4.3-2\"></path>
                <path d=\"M12 10a2 2 0 0 0-2 2c0 1 .2 2.2.8 3.5\"></path>
                <path d=\"M20 15c-1 3.5-3 5-5 5-2 0-3.5-1.5-4.5-3.5\"></path>
                <path d=\"M16 8.5a4.5 4.5 0 0 0-7 3.8c0 2 .5 4 1.5 6\"></path>
                <path d=\"M12 6a6 6 0 0 1 6 6c0 2-.5 4.5-1.5 6.5\"></path
            </svg>";

        case 'qr':
        case 'qrcode':
            return "<svg {$common}><rect x=\"3\" y=\"3\" width=\"7\" height=\"7\"></rect><rect x=\"14\" y=\"3\" width=\"7\" height=\"7\"></rect><rect x=\"14\" y=\"14\" width=\"7\" height=\"7\"></rect><rect x=\"3\" y=\"14\" width=\"7\" height=\"7\"></rect
            </svg>";

        case 'camera':
        case 'scan':
            return "<svg {$common}>
                <path d=\"M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z\"></path>
                <circle cx=\"12\" cy=\"13\" r=\"4\"></circle
            </svg>";

        case 'coin':
            return "<svg {$common}>
                <circle cx=\"12\" cy=\"12\" r=\"9\"></circle>
                <path d=\"M12 7v10\"></path>
                <path d=\"M9.5 9.5a2.5 2.5 0 0 1 5 0c0 1.5-2 2.5-2.5 3\"></path
            </svg>";

        case 'check':
            return "<svg {$common} stroke-width=\"2.5\"><polyline points=\"20 6 9 17 4 12\"></polyline></svg>";

        case 'clock':
            return "<svg {$common}><circle cx=\"12\" cy=\"12\" r=\"10\"></circle><polyline points=\"12 6 12 12 16 14\"></polyline></svg>";

        case 'info':
            return "<svg {$common}>
                <circle cx=\"12\" cy=\"12\" r=\"10\"></circle>
                <line x1=\"12\" y1=\"16\" x2=\"12\" y2=\"12\"></line>
                <line x1=\"12\" y1=\"8\" x2=\"12.01\" y2=\"8\"></line
            </svg>";

        case 'export':
            return "<svg {$common}>
                <path d=\"M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4\"></path>
                <polyline points=\"7 10 12 15 17 10\"></polyline>
                <line x1=\"12\" y1=\"15\" x2=\"12\" y2=\"3\"></line
            </svg>";

        case 'card':
            return "<svg {$common}><rect x=\"1\" y=\"4\" width=\"22\" height=\"16\" rx=\"2\" ry=\"2\"></rect>
                <line x1=\"1\" y1=\"10\" x2=\"23\" y2=\"10\"></line
            </svg>";

        default:
            return "";
    }
}
