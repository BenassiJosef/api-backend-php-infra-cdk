<?php

namespace App\Package\Profile;


/**
 * Class StubUserProfile
 * @package App\Package\Profile
 */
interface MinimalUserProfile
{
    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return string
     */
    public function getFirst(): ?string;

    /**
     * @return string
     */
    public function getLast(): ?string;

    /**
     * @return string
     */
    public function getEmail(): ?string;

    /**
     * @return string
     */
    public function getPhone(): ?string;

    /**
     * @return string
     */
    public function getGender(): ?string;

    /**
     * @return string
     */
    public function getPostCode(): ?string;

    /**
     * @return int
     */
    public function getBirthDay(): ?int;

    /**
     * @return int
     */
    public function getBirthMonth(): ?int;
}