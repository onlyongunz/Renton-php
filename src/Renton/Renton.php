<?php
namespace Onlyongunz\Renton;

/**
 * Proyek Renton; Translasi semua jadwal dari Student Portal menjadi file JSON.
 *
 * Proyek Renton adalah sebuah proyek kecil yang saya kerjakan untuk membantu saya
 * membuat visualisasi dari jadwal. Semenjak saya masih baru masuk, maka saya akan
 * banyak membuat penjadwalan agar tidak kelewatan kelas, tapi saya terlalu
 * malas untuk membuatnya tiap kali jadwal berganti setelah PRS, atau semester
 * baru.
 * Salah satu goal project ini untuk membantu para *geek* memproses datanya, dengan
 * mengkonversikan html dari Student Portal menjadi file JSON.
 * Setelah itu mereka dapat menggunakan file JSON tersebut dengan mudah sesuai keinginan
 * mereka.
 *
 * PHP versions 5 and 7
 * Biar banyak yang dukung :P
 *
 * @package    Onlyongunz\Renton
 * @author     Onlyongunz <gunawan.mr.blue@gmail.com>
 * @link       https://github.com/onlyongunz/Renton-php
 */

/**
 * Kelas utama Project Renton; Sumber siklus file. Kita hanya akan memerlukan
 * file html dari halaman cetak Student Portal anda.
 * @package Onlyongunz\Renton
 */
class Renton {

    private $html_location="",
        $jadwal_list=[],
        $identitas=null,
        $detil_jadwal=null;

    const
        JADWAL_PATTERN = [
            "jadwal"=>[
                "pattern"=>"/(<tr class\=\"(row_even|row_odd)\">)((.|\n)*?)(<\/tr>)/i",
                "offset"=>0
                ],
            "identitas"=>[
                "pattern"=>"/(((\<tr\>(.|\n)*?<\/tr>))+)/i",
                "offset"=>0
                ],

            "splitter"=>[
                "pattern"=>"/<table.*?>(.*?)<\/table>/si"
            ]
        ];

    /**
     * Method ini akan membaca html dan memparsing html tersebut
     * dan merepresentasikan menjadi sebuah objek yang bermakna.
     * 
     * @param $htmlfile Lokasi ke file html
     */
    public function parse_html($htmlfile) {
        $this->html_location=$htmlfile;
        $this->intepret_jadwal();
    }

    /**
     * Method ini akan membaca html dan memparsing html tersebut
     * dan merepresentasikan menjadi sebuah objek yang bermakna.
     * 
     * @param $html isi dari html tersebut
     */
    public function parse_html_data($html) {
        $this->intepret_jadwal($html);
    }

    /**
     * Merender data menjadi file JSON / string JSON.
     * data json akan mengandung 3 data penting:
     * identitas, detil_jadwal, dan kelas.
     * 
     * @param $json_path lokasi untuk file JSON yang akan ditulis
     * @param $json_options opsi JSON, siapa tau anda perlu.
     * @return JSON String (jika $json_path null) atau int dari penulis file 
     */
    public function toJSON($json_path = null, $json_options = null) {
        $objek = [
            "identitas"=>$this->identitas,
            "detil_jadwal"=>$this->detil_jadwal,
            "kelas"=>$this->jadwal_list
        ];
        if ($json_path==null) {
            return json_encode($objek, $json_options);
        }
        return file_put_contents($json_path, json_encode($objek, $json_options));
    }

    /**
     * Melempar listing jadwal kita
     *
     * @return jadwal list
     */
    public function get_list_jadwal() {
        return $this->jadwal_list;
    }

    /**
     * memulai parsing htmlnya, kita lakukan secara bertahap
     * 
     * @param $html isi dari html, autoload jika null
     */
    private function intepret_jadwal($html=null) {
        // Autoload html jika null
        if($html==null)
            $html=file_get_contents($this->html_location);
        
        //parsing jadwal kelas
        $jadwal_asli = $this->jadwal_split($html)[2][0];
        $jadwals=[];
        preg_match_all(
            self::JADWAL_PATTERN['jadwal']['pattern'],
            $jadwal_asli,
            $jadwals,
            null,
            self::JADWAL_PATTERN['jadwal']['offset']
        );
        $ruby = Model\Kelas::parseRombongan($jadwals[3]);
        $this->jadwal_list = $ruby;

        // parsing identitas pemilik
        $identitas=[];
        preg_match_all(
            self::JADWAL_PATTERN['identitas']['pattern'],
            $html,
            $identitas,
            null,
            self::JADWAL_PATTERN['identitas']['offset']
        );
        $identity = new Model\Identitas();
        $identity->parse(array_splice($identitas[0], 2, 3));
        $this->identitas=$identity;

        //parsing detil jadwal
        $detil_jadwal = new Model\JadwalMeta();
        $detil_jadwal->parse($html);
        $this->detil_jadwal = $detil_jadwal;
    }

    /**
     * Memilih table jadwal yang akan di parsing.
     * Ini dibuat karena ternyata pattern mendeteksi juga benda lain selain dari jadwal
     * kelas.
     *
     * @param $htmls isi file html yang akan kita cari tablenya
     * @return array of table
     */
    private function jadwal_split($htmls){
        $result=[];
        preg_match_all(self::JADWAL_PATTERN['splitter']['pattern'], $htmls, $result, PREG_SET_ORDER);
        return $result;
    }
}
