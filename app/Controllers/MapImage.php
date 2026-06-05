<?php

namespace App\Controllers;

class MapImage extends BaseController
{
    public function serve(string $key)
    {
        if (!auth()->loggedIn()) {
            return $this->response->setStatusCode(401);
        }

        $valid = ['AspenSnowmass', 'BigSkyCombo', 'DeerValley', 'Killington', 'PalisadesTahoe', 'ParkCity', 'Vail'];
        if (!in_array($key, $valid)) {
            return $this->response->setStatusCode(404);
        }

        $path = WRITEPATH . 'maps/' . $key . '.jpg';
        if (!is_file($path)) {
            return $this->response->setStatusCode(404);
        }

        $data = base64_encode(file_get_contents($path));
        return $this->response->setJSON(['data' => 'data:image/jpeg;base64,' . $data]);
    }
}
