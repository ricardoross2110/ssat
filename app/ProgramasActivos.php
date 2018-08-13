<?php

namespace App;

Class ProgramasActivos
{
	public $codigoPrograma;
	public $nombrePrograma;
    public $cursos = array();

    public function getCodigoPrograma()
    {
        return $this->codigoPrograma;
    }
    public function setCodigoPrograma($codigoPrograma)
    {
        $this->codigoPrograma = $codigoPrograma;
    }
    public function getNombrePrograma()
    {
        return $this->nombrePrograma;
    }
    public function setNombrePrograma($nombrePrograma)
    {
        $this->nombrePrograma = $nombrePrograma;
    }
    public function addCurso(CursoPrograma $curso)
    {
        $this->cursos = [$this->cursos, $curso];
    }
    public function getCurso()
    {
        return $this->cursos;
    }
}

class CursoPrograma
{
	private $codigoCurso;
	private $nombreCurso;

    /**
     * @return string
     */

    public function getCodigoCurso()
    {
        return $this->codigoCurso;
    }

    public function setCodigoCurso($codigoCurso)
    {
        $this->codigoCurso = $codigoCurso;
    }
    public function getNombreCurso()
    {
        return $this->nombreCurso;
    }

    public function setNombreCurso($nombreCurso)
    {
        $this->nombreCurso = $nombreCurso;
    }
}

class ProgramasActivosDTO
{
    public function __construct(ProgramasActivos $programasActivos)
    {
        $this->codigoPrograma = $programasActivos->getCodigoPrograma();
        $this->nombrePrograma = $programasActivos->getNombrePrograma();
    }

    public function getCodigoPrograma()
    {
        return $this->codigoPrograma;
    }

    public function getNombrePrograma()
    {
        return $this->nombrePrograma;
    }
    
}
